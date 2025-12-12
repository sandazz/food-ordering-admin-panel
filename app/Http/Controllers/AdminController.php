<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');

        // Load branches for selector if restaurant selected
        $branches = [];
        if ($restaurantId) {
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            foreach ($branchesDocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branches[] = [
                    'id' => $bid,
                    'name' => $bf['name']['stringValue'] ?? $bid,
                ];
            }
        }

        // Fetch orders from top-level `orders` collection, but use server-side filtering
        // to avoid pulling all documents into the app.
        $allOrders = [];
        try {
            $fieldFilters = [];
            if ($restaurantId) {
                $fieldFilters[] = ['fieldPath' => 'restaurantId', 'op' => 'EQUAL', 'value' => $restaurantId];
            }
            if ($branchId) {
                $fieldFilters[] = ['fieldPath' => 'branchId', 'op' => 'EQUAL', 'value' => $branchId];
            }

            if (!empty($fieldFilters)) {
                $ordersResp = $firebase->runStructuredQuery('orders', $fieldFilters);
            } else {
                // No filter provided; fall back to a simple collection read (small dev datasets)
                $ordersResp = $firebase->getCollection('orders');
            }

            $ordersDocs = $ordersResp['documents'] ?? [];
            foreach ($ordersDocs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $allOrders[$id] = $doc;
            }
        } catch (\Exception $e) {
            \Log::error('AdminController::dashboard - failed to fetch orders with structured query', ['error' => $e->getMessage()]);
            // fallback to safe empty set
            $allOrders = [];
            $ordersResp = null;
        }

        // Keep a reference to the last response for diagnostics
        $rawResponse = $ordersResp ?? null;

        // If no orders were found so far, log the last response and try scanning nested restaurant/branch paths
        $lastResp = $resp ?? $rawResponse ?? null;
        if (empty($allOrders)) {
            \Log::debug('AdminController::dashboard - no orders found from initial query', [
                'restaurantId' => $restaurantId,
                'branchId' => $branchId,
                'lastResponse' => $lastResp,
            ]);

            // Scan all restaurants -> branches -> orders to find nested documents
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            foreach ($restaurantsDocs as $rDoc) {
                $restId = Str::afterLast($rDoc['name'], '/');
                $branchesResp2 = $firebase->getCollection("restaurants/{$restId}/branches");
                $branchesDocs2 = $branchesResp2['documents'] ?? [];
                foreach ($branchesDocs2 as $bd2) {
                    $bId = Str::afterLast($bd2['name'], '/');
                    $resp2 = $firebase->getCollection("restaurants/{$restId}/branches/{$bId}/orders");
                    $docs2 = $resp2['documents'] ?? [];
                    foreach ($docs2 as $doc) {
                        $id = Str::afterLast($doc['name'], '/');
                        $allOrders[$id] = $doc;
                    }
                }
            }
        }

        // Fetch customers
        $customersResp = $firebase->getCollection('customers');
        $customerDocs = $customersResp['documents'] ?? [];
        $totalCustomers = count($customerDocs);

        // Calculate statistics
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $ordersCollection = collect($allOrders)->map(function($order) {
            $fields = $order['fields'] ?? [];
            
            // Parse createdAt - handle both timestampValue and direct timestamp
            $createdAtRaw = null;
            if (isset($fields['createdAt']['timestampValue'])) {
                $createdAtRaw = $fields['createdAt']['timestampValue'];
            } elseif (isset($fields['createdAt']['stringValue'])) {
                $createdAtRaw = $fields['createdAt']['stringValue'];
            }
            
            return [
                'totalAmount' => $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0,
                'status' => $fields['status']['stringValue'] ?? 'pending',
                'createdAt' => $createdAtRaw ? \Carbon\Carbon::parse($createdAtRaw) : now(),
                'rating' => $fields['rating']['doubleValue'] ?? $fields['rating']['integerValue'] ?? null,
            ];
        });

        // Today's orders
        $todayOrders = $ordersCollection->filter(fn($o) => $o['createdAt']->gte($todayStart));
        $todayRevenue = $todayOrders->sum('totalAmount');
        $todayOrdersCount = $todayOrders->count();

        // This week's orders
        $weekOrders = $ordersCollection->filter(fn($o) => $o['createdAt']->gte($weekStart));
        $weekOrdersCount = $weekOrders->count();

        // Last week's orders for comparison
        $lastWeekOrders = $ordersCollection->filter(fn($o) => 
            $o['createdAt']->gte($lastWeekStart) && $o['createdAt']->lte($lastWeekEnd)
        );
        $lastWeekOrdersCount = $lastWeekOrders->count();

        // This month's orders
        $monthOrders = $ordersCollection->filter(fn($o) => $o['createdAt']->gte($monthStart));
        $monthRevenue = $monthOrders->sum('totalAmount');

        // Last month's orders for comparison
        $lastMonthOrders = $ordersCollection->filter(fn($o) => 
            $o['createdAt']->gte($lastMonthStart) && $o['createdAt']->lte($lastMonthEnd)
        );
        $lastMonthRevenue = $lastMonthOrders->sum('totalAmount');

        // Calculate percentage changes
        $ordersGrowth = $lastWeekOrdersCount > 0 
            ? round((($weekOrdersCount - $lastWeekOrdersCount) / $lastWeekOrdersCount) * 100, 1)
            : 0;

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Active orders (pending, confirmed, preparing, ready, out_for_delivery)
        $activeStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed'];
        $activeOrders = $ordersCollection->filter(fn($o) => in_array($o['status'], $activeStatuses))->count();

        // Average rating
        $ratingsCollection = $ordersCollection->filter(fn($o) => $o['rating'] !== null);
        $avgRating = $ratingsCollection->count() > 0 
            ? round($ratingsCollection->avg('rating'), 1)
            : 0;

        // Recent orders (last 10)
        $recentOrders = collect($allOrders)
            ->sortByDesc(function($order) {
                $fields = $order['fields'] ?? [];
                return $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '';
            })
            ->take(10)
            ->toArray();

        // Performance metrics
        $completedOrders = $ordersCollection->filter(fn($o) => $o['status'] === 'completed');
        $cancelledOrders = $ordersCollection->filter(fn($o) => $o['status'] === 'cancelled');
        $successRate = $ordersCollection->count() > 0
            ? round(($completedOrders->count() / $ordersCollection->count()) * 100, 1)
            : 0;

        $avgOrderValue = $ordersCollection->count() > 0
            ? $ordersCollection->avg('totalAmount')
            : 0;

        return view('admin.dashboard', [
            'recentOrders' => $recentOrders,
            'branches' => $branches,
            'currentBranchId' => $branchId,
            'stats' => [
                'totalOrders' => $todayOrdersCount,
                'totalRevenue' => $monthRevenue,
                'activeOrders' => $activeOrders,
                'avgRating' => $avgRating,
                'ordersGrowth' => $ordersGrowth,
                'revenueGrowth' => $revenueGrowth,
                'totalCustomers' => $totalCustomers,
                'completedToday' => $todayOrders->filter(fn($o) => $o['status'] === 'completed')->count(),
                'pendingOrders' => $ordersCollection->filter(fn($o) => $o['status'] === 'pending')->count(),
                'cancelledOrders' => $cancelledOrders->count(),
                'successRate' => $successRate,
                'avgOrderValue' => $avgOrderValue,
            ],
            'allOrdersCount' => $ordersCollection->count(),
        ]);
    }

    // Add stubs for other admin panel pages
    public function orders() { return view('admin.orders'); }
    public function menu() { return view('admin.menu'); }
    public function staff() { return view('admin.staff'); }
    public function reports() { return view('admin.reports'); }
    public function notifications() { return view('admin.notifications'); }
    public function settings() { return view('admin.settings'); }
}