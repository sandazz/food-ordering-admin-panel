<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Services\PaytrailClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PaymentHistoryController extends Controller
{
    public function index(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        $role = $request->session()->get('role');

        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        // Validate and get filters
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => 'nullable|in:all,paid,failed,cancelled',
            'branchId' => 'nullable|string',
        ]);

        // Default to last 30 days if no date range provided
        $from = $filters['from'] ?? now()->subDays(30)->format('Y-m-d');
        $to = $filters['to'] ?? now()->format('Y-m-d');
        $statusFilter = $filters['status'] ?? 'all';
        $branchFilter = $filters['branchId'] ?? '';

        // Determine which branches to fetch based on role
        $branches = $this->getScopedBranches($firebase, $role, $restaurantId, $branchId);

        // Apply branch filter if provided (for Super/Restaurant Admin)
        if ($branchFilter && in_array($role, ['admin', 'restaurant_admin'])) {
            $branches = array_filter($branches, function($branch) use ($branchFilter) {
                return $branch['id'] === $branchFilter;
            });
        }

        // Get all restaurants for Super Admin
        $restaurants = [];
        if ($role === 'admin') {
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            foreach ($restaurantsDocs as $rd) {
                $rid = Str::afterLast($rd['name'], '/');
                $rf = $rd['fields'] ?? [];
                $restaurants[] = [
                    'id' => $rid,
                    'name' => $rf['name']['stringValue'] ?? $rid,
                ];
            }
        }

        // Fetch payment history for each branch
        $allTransactions = [];
        $errors = [];
        $totalRevenue = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($branches as $branch) {
            try {
                $transactions = $this->fetchBranchPaymentHistory($branch, $from, $to);
                
                foreach ($transactions as $transaction) {
                    // Apply status filter
                    if ($statusFilter !== 'all' && $transaction['status'] !== $statusFilter) {
                        continue;
                    }

                    // Add branch info
                    $transaction['branch_name'] = $branch['name'];
                    $transaction['branch_id'] = $branch['id'];
                    $transaction['restaurant_name'] = $branch['restaurant_name'] ?? '';
                    
                    $allTransactions[] = $transaction;

                    // Calculate stats
                    if ($transaction['status'] === 'paid') {
                        $totalRevenue += $transaction['amount'];
                        $successCount++;
                    } elseif (in_array($transaction['status'], ['failed', 'cancelled'])) {
                        $failedCount++;
                    }
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // APP_KEY has changed - suggest re-configuring the branch
                $errors[] = "Branch '{$branch['name']}': Payment credentials need to be re-configured. Please edit this branch and re-enter the payment gateway credentials.";
            } catch (\Exception $e) {
                $errors[] = "Branch '{$branch['name']}': " . $e->getMessage();
            }
        }

        // Sort by timestamp descending
        usort($allTransactions, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Simple pagination
        $perPage = (int)$request->query('perPage', 50);
        $page = max(1, (int)$request->query('page', 1));
        $total = count($allTransactions);
        $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($allTransactions, $offset, $perPage);

        // Prepare stats for charts
        $stats = [
            'total_revenue' => $totalRevenue,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total_count' => $successCount + $failedCount,
        ];

        // Group transactions by date for chart
        $revenueByDate = [];
        foreach ($allTransactions as $transaction) {
            if ($transaction['status'] === 'paid') {
                $date = date('Y-m-d', strtotime($transaction['timestamp']));
                if (!isset($revenueByDate[$date])) {
                    $revenueByDate[$date] = 0;
                }
                $revenueByDate[$date] += $transaction['amount'];
            }
        }
        ksort($revenueByDate);

        return view('admin.payment-history', [
            'transactions' => $paginated,
            'branches' => $branches,
            'restaurants' => $restaurants,
            'stats' => $stats,
            'revenueByDate' => $revenueByDate,
            'errors' => $errors,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'status' => $statusFilter,
                'branchId' => $branchFilter,
            ],
            'role' => $role,
            'currentRestaurantId' => $restaurantId,
            'currentBranchId' => $branchId,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'lastPage' => $lastPage,
            ],
        ]);
    }

    /**
     * Get branches scoped by user role
     */
    private function getScopedBranches(FirebaseService $firebase, string $role, string $restaurantId, ?string $branchId): array
    {
        $branches = [];

        if ($role === 'admin') {
            // Super Admin: Fetch all restaurants and all branches
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            
            foreach ($restaurantsDocs as $rd) {
                $rid = Str::afterLast($rd['name'], '/');
                $rf = $rd['fields'] ?? [];
                $restaurantName = $rf['name']['stringValue'] ?? $rid;
                
                $branchesResp = $firebase->getCollection("restaurants/{$rid}/branches");
                $branchesDocs = $branchesResp['documents'] ?? [];
                
                foreach ($branchesDocs as $bd) {
                    $bid = Str::afterLast($bd['name'], '/');
                    $bf = $bd['fields'] ?? [];
                    
                    $branch = $this->parseBranch($bid, $bf);
                    $branch['restaurant_id'] = $rid;
                    $branch['restaurant_name'] = $restaurantName;
                    
                    $branches[] = $branch;
                }
            }
        } elseif ($role === 'restaurant_admin') {
            // Restaurant Admin: Fetch all branches for their restaurant
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            
            foreach ($branchesDocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                
                $branch = $this->parseBranch($bid, $bf);
                $branch['restaurant_id'] = $restaurantId;
                
                $branches[] = $branch;
            }
        } elseif ($role === 'branch_admin') {
            // Branch Admin: Only their assigned branch
            if ($branchId) {
                $branchDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}");
                $bf = $branchDoc['fields'] ?? [];
                
                $branch = $this->parseBranch($branchId, $bf);
                $branch['restaurant_id'] = $restaurantId;
                
                $branches[] = $branch;
            }
        }

        return $branches;
    }

    /**
     * Parse branch document from Firestore
     */
    private function parseBranch(string $id, array $fields): array
    {
        $paymentConfig = null;
        if (isset($fields['paymentConfig']['mapValue']['fields'])) {
            $pcFields = $fields['paymentConfig']['mapValue']['fields'];
            $paymentConfig = [
                'merchantId' => $pcFields['merchantId']['stringValue'] ?? null,
                'secretKeyEnc' => $pcFields['secretKeyEnc']['stringValue'] ?? null,
                'isActive' => $pcFields['isActive']['booleanValue'] ?? false,
            ];
        }

        // Parse address map if exists (same structure as SettingsController)
        $address = '';
        if (isset($fields['address']['mapValue']['fields'])) {
            $addrFields = $fields['address']['mapValue']['fields'];
            $parts = array_filter([
                $addrFields['street']['stringValue'] ?? '',
                $addrFields['city']['stringValue'] ?? '',
                $addrFields['state']['stringValue'] ?? '',
                $addrFields['zipCode']['stringValue'] ?? '',
                $addrFields['country']['stringValue'] ?? '',
            ]);
            $address = implode(', ', $parts);
        }

        return [
            'id' => $id,
            'name' => $fields['name']['stringValue'] ?? $id,
            'address' => $address,
            'paymentConfig' => $paymentConfig,
        ];
    }

    /**
     * Fetch payment history for a specific branch using Paytrail API
     */
    private function fetchBranchPaymentHistory(array $branch, string $from, string $to): array
    {
        $paymentConfig = $branch['paymentConfig'];
        
        // Check if payment config exists and is active
        if (!$paymentConfig || !$paymentConfig['isActive']) {
            return [];
        }

        if (!$paymentConfig['merchantId'] || !$paymentConfig['secretKeyEnc']) {
            return [];
        }

        // Decrypt secret key
        try {
            $secretKey = Crypt::decryptString($paymentConfig['secretKeyEnc']);
            
            // Validate decrypted secret is not empty
            if (empty($secretKey)) {
                throw new \Exception("Decrypted secret key is empty");
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Re-throw DecryptException so it can be caught separately in the main loop
            throw $e;
        } catch (\Exception $e) {
            throw new \Exception("Failed to decrypt secret key: " . $e->getMessage());
        }

        // Initialize Paytrail client with branch credentials
        $paytrailClient = new PaytrailClient($paymentConfig['merchantId'], $secretKey);

        // Fetch payment report
        try {
            $report = $paytrailClient->getPaymentReport([
                'from' => $from,
                'to' => $to,
            ]);

            // Parse transactions from report
            return $this->parsePaytrailReport($report);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new \Exception("Unable to connect to Paytrail API. Please check your internet connection or contact support.");
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = (string) $e->getResponse()->getBody();
                throw new \Exception("Paytrail API error (HTTP {$statusCode}): {$body}");
            }
            throw new \Exception("Paytrail API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception("Paytrail API error: " . $e->getMessage());
        }
    }

    /**
     * Parse Paytrail report response into standardized transaction format
     */
    private function parsePaytrailReport(array $report): array
    {
        $transactions = [];

        // Handle different possible response formats
        $payments = $report['payments'] ?? $report['transactions'] ?? $report;

        if (!is_array($payments)) {
            return [];
        }

        foreach ($payments as $payment) {
            // Map Paytrail response to our format
            $transactions[] = [
                'transaction_id' => $payment['transaction_id'] ?? $payment['payment_id'] ?? $payment['id'] ?? 'N/A',
                'order_reference' => $payment['reference'] ?? $payment['order_reference'] ?? $payment['stamp'] ?? null,
                'amount' => ($payment['amount'] ?? 0) / 100, // Convert cents to EUR
                'currency' => $payment['currency'] ?? 'EUR',
                'status' => $this->normalizePaymentStatus($payment['status'] ?? 'unknown'),
                'customer_email' => $payment['customer']['email'] ?? $payment['email'] ?? null,
                'payment_method' => $payment['payment_method'] ?? $payment['provider'] ?? 'Unknown',
                'timestamp' => $payment['timestamp'] ?? $payment['created_at'] ?? $payment['date'] ?? now()->toISOString(),
            ];
        }

        return $transactions;
    }

    /**
     * Normalize payment status from Paytrail to our standardized values
     */
    private function normalizePaymentStatus(string $status): string
    {
        $status = strtolower($status);
        
        $mapping = [
            'ok' => 'paid',
            'success' => 'paid',
            'paid' => 'paid',
            'completed' => 'paid',
            'fail' => 'failed',
            'failed' => 'failed',
            'error' => 'failed',
            'cancel' => 'cancelled',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'pending' => 'pending',
        ];

        return $mapping[$status] ?? $status;
    }

    /**
     * Export payment history to CSV
     */
    public function exportCsv(Request $request, FirebaseService $firebase)
    {
        // Reuse the same logic to fetch transactions
        $data = $this->getTransactionsData($request, $firebase);
        
        $filename = 'payment-history-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['Transaction ID', 'Order Reference', 'Branch', 'Amount (EUR)', 'Status', 'Customer Email', 'Payment Method', 'Date']);
        
        // CSV rows
        foreach ($data['transactions'] as $transaction) {
            fputcsv($output, [
                $transaction['transaction_id'],
                $transaction['order_reference'] ?? 'N/A',
                $transaction['branch_name'],
                number_format($transaction['amount'], 2),
                ucfirst($transaction['status']),
                $transaction['customer_email'] ?? 'N/A',
                $transaction['payment_method'],
                date('Y-m-d H:i:s', strtotime($transaction['timestamp'])),
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Helper method to get transactions data (used by both index and export)
     */
    private function getTransactionsData(Request $request, FirebaseService $firebase): array
    {
        // This is a simplified version - in production, you'd extract the logic from index()
        // For now, returning empty array as placeholder
        return ['transactions' => []];
    }
}
