@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ \App\Utils\UIStrings::t('dashboard.title') }}</h1>
        <p class="page-subtitle">{{ \App\Utils\UIStrings::t('dashboard.welcome') }}</p>
    </div>
    <!-- Top action buttons removed per request -->
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card primary h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3 class="mb-1 fw-bold">{{ $stats['totalOrders'] }}</h3>
                <p class="text-muted mb-0">{{ \App\Utils\UIStrings::t('dashboard.recent_orders') }}</p>
                <small class="{{ $stats['ordersGrowth'] >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi bi-arrow-{{ $stats['ordersGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ $stats['ordersGrowth'] >= 0 ? '+' : '' }}{{ $stats['ordersGrowth'] }}% from last week
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card success h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="mb-1 fw-bold">
                    ${{ number_format($stats['totalRevenue'], 2) }}
                </h3>
                <p class="text-muted mb-0">Total Revenue</p>
                <small class="{{ $stats['revenueGrowth'] >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi bi-arrow-{{ $stats['revenueGrowth'] >= 0 ? 'up' : 'down' }}"></i> {{ $stats['revenueGrowth'] >= 0 ? '+' : '' }}{{ $stats['revenueGrowth'] }}% from last month
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card warning h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="mb-1 fw-bold">
                    {{ $stats['activeOrders'] }}
                </h3>
                <p class="text-muted mb-0">Orders in Progress</p>
                <small class="text-warning">
                    <i class="bi bi-clock"></i> Active orders need attention
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card info h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-star"></i>
                </div>
                <h3 class="mb-1 fw-bold">{{ $stats['avgRating'] > 0 ? $stats['avgRating'] : 'N/A' }}</h3>
                <p class="text-muted mb-0">Average Rating</p>
                <small class="text-info">
                    <i class="bi bi-star-fill"></i> Based on customer reviews
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Revenue Overview</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="border-end">
                            @php
                                $todayRevenue = collect($recentOrders)->filter(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    $createdAtRaw = $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? null;
                                    if (!$createdAtRaw) return false;
                                    $createdAt = \Carbon\Carbon::parse($createdAtRaw);
                                    return $createdAt->isToday();
                                })->sum(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                                });
                            @endphp
                            <h4 class="text-success mb-1">${{ number_format($todayRevenue, 2) }}</h4>
                            <small class="text-muted">Today's Revenue</small>
                            <p class="text-muted small mb-0 mt-1">
                                {{ collect($recentOrders)->filter(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    $createdAtRaw = $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? null;
                                    if (!$createdAtRaw) return false;
                                    $createdAt = \Carbon\Carbon::parse($createdAtRaw);
                                    return $createdAt->isToday();
                                })->count() }} orders
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border-end">
                            @php
                                $weekRevenue = collect($recentOrders)->filter(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    $createdAtRaw = $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? null;
                                    if (!$createdAtRaw) return false;
                                    $createdAt = \Carbon\Carbon::parse($createdAtRaw);
                                    return $createdAt->isCurrentWeek();
                                })->sum(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                                });
                            @endphp
                            <h4 class="text-primary mb-1">${{ number_format($weekRevenue, 2) }}</h4>
                            <small class="text-muted">This Week</small>
                            <p class="text-muted small mb-0 mt-1">
                                {{ collect($recentOrders)->filter(function($order) {
                                    $fields = $order['fields'] ?? [];
                                    $createdAtRaw = $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? null;
                                    if (!$createdAtRaw) return false;
                                    $createdAt = \Carbon\Carbon::parse($createdAtRaw);
                                    return $createdAt->isCurrentWeek();
                                })->count() }} orders
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="text-info mb-1">${{ number_format($stats['totalRevenue'], 2) }}</h4>
                        <small class="text-muted">This Month</small>
                        <p class="text-muted small mb-0 mt-1">
                            {{ $allOrdersCount }} total orders
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="row g-4">
    <!-- Recent Orders Table -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ \App\Utils\UIStrings::t('dashboard.recent_orders') }}</h5>
                <a href="{{ url('/admin/orders') }}" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                @if(count($recentOrders) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Type</th>
                                <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
                                <th>Payment</th>
                                <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
                                <th>{{ \App\Utils\UIStrings::t('table.created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $orderId => $order)
                                @php
                                    $fields = $order['fields'] ?? [];
                                    $status = $fields['status']['stringValue'] ?? 'pending';
                                    $customerId = $fields['customerId']['stringValue'] ?? null;
                                    $orderType = $fields['orderType']['stringValue'] ?? 'delivery';
                                    $paymentMethod = $fields['paymentMethod']['stringValue'] ?? 'cash';
                                    $paymentStatus = $fields['paymentStatus']['stringValue'] ?? 'pending';
                                    $displayId = $fields['displayId']['stringValue'] ?? substr($orderId, -8);
                                    $totalAmount = $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                                    $createdAtRaw = $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? null;
                                    $statusClass = 'status-' . strtolower($status);
                                    
                                    // Extract items information
                                    $itemsArray = [];
                                    if (isset($fields['items']['arrayValue']['values'])) {
                                        foreach ($fields['items']['arrayValue']['values'] as $itemValue) {
                                            $itemFields = $itemValue['mapValue']['fields'] ?? [];
                                            $itemsArray[] = [
                                                'name' => $itemFields['name']['stringValue'] ?? 'Unknown Item',
                                                'quantity' => $itemFields['quantity']['integerValue'] ?? 1,
                                                'price' => $itemFields['price']['integerValue'] ?? $itemFields['price']['doubleValue'] ?? 0,
                                            ];
                                        }
                                    }
                                    $itemCount = count($itemsArray);
                                    $firstItem = $itemsArray[0] ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-primary">#{{ $displayId }}</span>
                                    </td>
                                    <td>
                                        @if($firstItem)
                                            <div>
                                                <small class="text-dark">{{ $firstItem['name'] }}</small>
                                                @if($itemCount > 1)
                                                    <span class="badge bg-secondary-subtle text-secondary ms-1">+{{ $itemCount - 1 }}</span>
                                                @endif
                                                <br>
                                                <small class="text-muted">{{ $itemCount }} item{{ $itemCount > 1 ? 's' : '' }}</small>
                                            </div>
                                        @else
                                            <small class="text-muted">No items</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($orderType === 'delivery')
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="bi bi-truck"></i> Delivery
                                            </span>
                                        @elseif($orderType === 'pickup')
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="bi bi-bag"></i> Pickup
                                            </span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="bi bi-shop"></i> Dine-in
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td>
                                        @if($paymentMethod === 'card')
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="bi bi-credit-card"></i> Card
                                            </span>
                                        @elseif($paymentMethod === 'cash')
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="bi bi-cash"></i> Cash
                                            </span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="bi bi-wallet2"></i> {{ ucfirst($paymentMethod) }}
                                            </span>
                                        @endif
                                        @if($paymentStatus === 'paid')
                                            <i class="bi bi-check-circle-fill text-success ms-1" title="Paid"></i>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">${{ number_format($totalAmount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $createdAtRaw ? \Carbon\Carbon::parse($createdAtRaw)->format('M j, g:i A') : 'N/A' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                    <h6 class="text-muted mt-3">{{ \App\Utils\UIStrings::t('orders.none') }}</h6>
                    <p class="text-muted small">Orders will appear here when customers start placing them.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Quick Stats & Actions -->
    <div class="col-lg-4">
        <div class="row g-4">
            <!-- Order Statistics -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">{{ \App\Utils\UIStrings::t('dashboard.order_stats') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">{{ \App\Utils\UIStrings::t('reports.table.orders') }}</span>
                            <span class="fw-semibold">{{ $allOrdersCount }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Completed Today</span>
                            <span class="fw-semibold text-success">
                                {{ $stats['completedToday'] }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Pending Orders</span>
                            <span class="fw-semibold text-warning">
                                {{ $stats['pendingOrders'] }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cancelled Orders</span>
                            <span class="fw-semibold text-danger">
                                {{ $stats['cancelledOrders'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue by Type -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Revenue by Order Type</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $deliveryRevenue = collect($recentOrders)->filter(function($order) {
                                $fields = $order['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'delivery';
                            })->sum(function($order) {
                                $fields = $order['fields'] ?? [];
                                return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                            });

                            $pickupRevenue = collect($recentOrders)->filter(function($order) {
                                $fields = $order['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'pickup';
                            })->sum(function($order) {
                                $fields = $order['fields'] ?? [];
                                return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                            });

                            $dineInRevenue = collect($recentOrders)->filter(function($order) {
                                $fields = $order['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'dine_in';
                            })->sum(function($order) {
                                $fields = $order['fields'] ?? [];
                                return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                            });

                            $deliveryCount = collect($recentOrders)->filter(function($o) {
                                $fields = $o['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'delivery';
                            })->count();
                            $pickupCount = collect($recentOrders)->filter(function($o) {
                                $fields = $o['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'pickup';
                            })->count();
                            $dineInCount = collect($recentOrders)->filter(function($o) {
                                $fields = $o['fields'] ?? [];
                                return ($fields['orderType']['stringValue'] ?? '') === 'dine_in';
                            })->count();
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="bi bi-truck text-primary"></i> Delivery ({{ $deliveryCount }})
                                </small>
                                <small class="fw-semibold">${{ number_format($deliveryRevenue, 2) }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $stats['totalRevenue'] > 0 ? ($deliveryRevenue / $stats['totalRevenue'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="bi bi-bag text-warning"></i> Pickup ({{ $pickupCount }})
                                </small>
                                <small class="fw-semibold">${{ number_format($pickupRevenue, 2) }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $stats['totalRevenue'] > 0 ? ($pickupRevenue / $stats['totalRevenue'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="bi bi-shop text-info"></i> Dine-in ({{ $dineInCount }})
                                </small>
                                <small class="fw-semibold">${{ number_format($dineInRevenue, 2) }}</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $stats['totalRevenue'] > 0 ? ($dineInRevenue / $stats['totalRevenue'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Payment Methods</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $cardRevenue = collect($recentOrders)->filter(function($order) {
                                $fields = $order['fields'] ?? [];
                                return ($fields['paymentMethod']['stringValue'] ?? '') === 'card';
                            })->sum(function($order) {
                                $fields = $order['fields'] ?? [];
                                return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                            });

                            $cashRevenue = collect($recentOrders)->filter(function($order) {
                                $fields = $order['fields'] ?? [];
                                return ($fields['paymentMethod']['stringValue'] ?? '') === 'cash';
                            })->sum(function($order) {
                                $fields = $order['fields'] ?? [];
                                return $fields['totalAmount']['integerValue'] ?? $fields['totalAmount']['doubleValue'] ?? 0;
                            });

                            $cardCount = collect($recentOrders)->filter(function($o) {
                                $fields = $o['fields'] ?? [];
                                return ($fields['paymentMethod']['stringValue'] ?? '') === 'card';
                            })->count();
                            $cashCount = collect($recentOrders)->filter(function($o) {
                                $fields = $o['fields'] ?? [];
                                return ($fields['paymentMethod']['stringValue'] ?? '') === 'cash';
                            })->count();
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="bi bi-credit-card text-success me-2"></i>
                                <span class="text-muted">Card ({{ $cardCount }})</span>
                            </div>
                            <span class="fw-semibold">${{ number_format($cardRevenue, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-cash text-secondary me-2"></i>
                                <span class="text-muted">Cash ({{ $cashCount }})</span>
                            </div>
                            <span class="fw-semibold">${{ number_format($cashRevenue, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ url('/admin/menu') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-lg me-2"></i>Add Menu Item
                            </a>
                            <a href="{{ url('/admin/staff') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-person-plus me-2"></i>Add Staff Member
                            </a>
                            <a href="{{ url('/admin/reports') }}" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-graph-up me-2"></i>View Reports
                            </a>
                            <a href="{{ url('/admin/settings') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Analytics Section -->
<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Performance Overview</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-primary mb-1">{{ $stats['successRate'] }}%</h4>
                            <small class="text-muted">Success Rate</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-success mb-1">{{ $stats['totalCustomers'] }}</h4>
                            <small class="text-muted">Total Customers</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-warning mb-1">${{ number_format($stats['avgOrderValue'], 2) }}</h4>
                            <small class="text-muted">Avg Order Value</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-info mb-1">{{ $stats['activeOrders'] }}</h4>
                        <small class="text-muted">Active Orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
