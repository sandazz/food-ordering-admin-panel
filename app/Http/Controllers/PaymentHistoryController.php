<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

/**
 * Payment History Controller
 * 
 * NOTE: Paytrail API does NOT provide an endpoint for listing/querying payments by date range.
 * The only available methods are:
 * - POST /payments (create payment)
 * - GET /payments/{transactionId} (verify individual payment)
 * 
 * Therefore, this controller queries payment history from our Firestore 'payments' collection,
 * which is populated during payment creation (initiate) and updated during callbacks (verify).
 * This is the compliant and correct approach for maintaining payment history with Paytrail.
 */
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
            'status' => 'nullable|in:all,paid,failed,cancelled,pending',
            'branchId' => 'nullable|string',
        ]);

        // Default to last 30 days if no date range provided
        $from = $filters['from'] ?? now()->subDays(30)->format('Y-m-d');
        $to = $filters['to'] ?? now()->format('Y-m-d');
        $statusFilter = $filters['status'] ?? 'all';
        $branchFilter = $filters['branchId'] ?? '';

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

        // Fetch all branches for dropdowns (scoped by role)
        $branches = $this->getScopedBranches($firebase, $role, $restaurantId, $branchId);

        // Fetch payments from Firestore with role-based filtering
        $allTransactions = $this->fetchPaymentsFromFirestore(
            $firebase,
            $role,
            $restaurantId,
            $branchId,
            $from,
            $to,
            $statusFilter,
            $branchFilter
        );

        // Calculate stats
        $totalRevenue = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($allTransactions as $transaction) {
            if ($transaction['status'] === 'paid') {
                $totalRevenue += $transaction['amount'];
                $successCount++;
            } elseif (in_array($transaction['status'], ['failed', 'cancelled'])) {
                $failedCount++;
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
            'errors' => [], // No API errors since we're querying Firestore
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
     * Fetch payments from Firestore 'payments' collection with role-based scoping and filters
     */
    private function fetchPaymentsFromFirestore(
        FirebaseService $firebase,
        string $role,
        string $restaurantId,
        ?string $branchId,
        string $from,
        string $to,
        string $statusFilter,
        string $branchFilter
    ): array {
        // Fetch all payments from Firestore
        $paymentsResp = $firebase->getCollection('payments');
        $paymentsDocs = $paymentsResp['documents'] ?? [];

        $transactions = [];
        $branchCache = []; // Cache branch names to avoid repeated lookups

        foreach ($paymentsDocs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];

            // Parse payment document
            $payment = $this->parsePayment($id, $f);

            // Apply role-based scoping
            if ($role === 'branch_admin') {
                // Branch Admin: Only their assigned branch
                if ($payment['restaurant_id'] !== $restaurantId || $payment['branch_id'] !== $branchId) {
                    continue;
                }
            } elseif ($role === 'restaurant_admin') {
                // Restaurant Admin: Only their restaurant
                if ($payment['restaurant_id'] !== $restaurantId) {
                    continue;
                }
            }
            // Super Admin: sees all payments (no filter)

            // Apply date filter
            $createdAt = strtotime($payment['timestamp']);
            $fromTs = strtotime($from . ' 00:00:00');
            $toTs = strtotime($to . ' 23:59:59');
            
            if ($createdAt < $fromTs || $createdAt > $toTs) {
                continue;
            }

            // Apply status filter
            if ($statusFilter !== 'all' && $payment['status'] !== $statusFilter) {
                continue;
            }

            // Apply branch filter (for Super/Restaurant Admin)
            if ($branchFilter && $payment['branch_id'] !== $branchFilter) {
                continue;
            }

            // Fetch branch name for display
            if (!isset($branchCache[$payment['branch_id']])) {
                try {
                    $branchDoc = $firebase->getDocument(
                        "restaurants/{$payment['restaurant_id']}/branches",
                        $payment['branch_id']
                    );
                    $bf = $branchDoc['fields'] ?? [];
                    $branchCache[$payment['branch_id']] = $bf['name']['stringValue'] ?? $payment['branch_id'];
                } catch (\Exception $e) {
                    $branchCache[$payment['branch_id']] = $payment['branch_id'];
                }
            }

            $payment['branch_name'] = $branchCache[$payment['branch_id']];
            $transactions[] = $payment;
        }

        return $transactions;
    }

    /**
     * Parse payment document from Firestore
     */
    private function parsePayment(string $id, array $fields): array
    {
        return [
            'transaction_id' => $fields['transaction_id']['stringValue'] ?? $id,
            'order_reference' => $fields['order_id']['stringValue'] ?? null,
            'order_display_id' => $fields['order_display_id']['stringValue'] ?? ($fields['order_id']['stringValue'] ?? null),
            'restaurant_id' => $fields['restaurant_id']['stringValue'] ?? '',
            'branch_id' => $fields['branch_id']['stringValue'] ?? '',
            'amount' => isset($fields['amount']['doubleValue']) 
                ? (float)$fields['amount']['doubleValue'] 
                : (float)($fields['amount']['integerValue'] ?? 0),
            'currency' => $fields['currency']['stringValue'] ?? 'EUR',
            'status' => $fields['status']['stringValue'] ?? 'pending',
            'customer_email' => $fields['customer_email']['stringValue'] ?? null,
            'payment_method' => $fields['payment_method']['stringValue'] ?? 'Unknown',
            'timestamp' => $fields['created_at']['timestampValue'] ?? ($fields['created_at']['stringValue'] ?? now()->toISOString()),
        ];
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
                    
                    $branches[] = [
                        'id' => $bid,
                        'name' => $bf['name']['stringValue'] ?? $bid,
                        'restaurant_id' => $rid,
                        'restaurant_name' => $restaurantName,
                    ];
                }
            }
        } elseif ($role === 'restaurant_admin') {
            // Restaurant Admin: Fetch all branches for their restaurant
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            
            foreach ($branchesDocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                
                $branches[] = [
                    'id' => $bid,
                    'name' => $bf['name']['stringValue'] ?? $bid,
                    'restaurant_id' => $restaurantId,
                ];
            }
        } elseif ($role === 'branch_admin') {
            // Branch Admin: Only their assigned branch
            if ($branchId) {
                $branchDoc = $firebase->getDocument("restaurants/{$restaurantId}/branches/{$branchId}");
                $bf = $branchDoc['fields'] ?? [];
                
                $branches[] = [
                    'id' => $branchId,
                    'name' => $bf['name']['stringValue'] ?? $branchId,
                    'restaurant_id' => $restaurantId,
                ];
            }
        }

        return $branches;
    }

    /**
     * Export payment history to CSV
     */
    public function exportCsv(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        $role = $request->session()->get('role');

        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        // Get filters
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));
        $statusFilter = $request->input('status', 'all');
        $branchFilter = $request->input('branchId', '');

        // Fetch payments
        $transactions = $this->fetchPaymentsFromFirestore(
            $firebase,
            $role,
            $restaurantId,
            $branchId,
            $from,
            $to,
            $statusFilter,
            $branchFilter
        );

        // Sort by timestamp descending
        usort($transactions, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        $filename = 'payment-history-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['Transaction ID', 'Order ID', 'Order Display ID', 'Branch', 'Amount (EUR)', 'Currency', 'Status', 'Customer Email', 'Payment Method', 'Date']);
        
        // CSV rows
        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['transaction_id'],
                $transaction['order_reference'] ?? 'N/A',
                $transaction['order_display_id'] ?? 'N/A',
                $transaction['branch_name'] ?? 'N/A',
                number_format($transaction['amount'], 2),
                $transaction['currency'],
                ucfirst($transaction['status']),
                $transaction['customer_email'] ?? 'N/A',
                $transaction['payment_method'],
                date('Y-m-d H:i:s', strtotime($transaction['timestamp'])),
            ]);
        }
        
        fclose($output);
        exit;
    }
}
