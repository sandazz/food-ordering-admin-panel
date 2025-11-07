@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ \App\Utils\UIStrings::t('dashboard.title') }}</h1>
        <p class="page-subtitle">{{ \App\Utils\UIStrings::t('dashboard.welcome') }}</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary">
            <i class="bi bi-download me-2"></i>Export Data
        </button>
        <button class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Quick Action
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card primary h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3 class="mb-1 fw-bold">{{ count($recentOrders) }}</h3>
                <p class="text-muted mb-0">{{ \App\Utils\UIStrings::t('dashboard.recent_orders') }}</p>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> +12% from last week
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
                    ${{ number_format(collect($recentOrders)->sum(function($order) {
                        return $order['fields']['totalAmount']['doubleValue'] ?? $order['fields']['totalAmount']['integerValue'] ?? 0;
                    }), 2) }}
                </h3>
                <p class="text-muted mb-0">Total Revenue</p>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> +8% from last month
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
                    {{ collect($recentOrders)->where('fields.status.stringValue', 'preparing')->count() }}
                </h3>
                <p class="text-muted mb-0">Orders in Progress</p>
                <small class="text-warning">
                    <i class="bi bi-clock"></i> Avg 15 min prep time
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
                <h3 class="mb-1 fw-bold">4.8</h3>
                <p class="text-muted mb-0">Average Rating</p>
                <small class="text-info">
                    <i class="bi bi-arrow-up"></i> +0.2 from last month
                </small>
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
                                <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
                                <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
                                <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
                                <th>{{ \App\Utils\UIStrings::t('table.created') }}</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $orderId => $order)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-primary">#{{ substr($orderId, -8) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $status = $order['fields']['status']['stringValue'] ?? 'pending';
                                            $statusClass = 'status-' . strtolower($status);
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">${{ number_format($order['fields']['totalAmount']['doubleValue'] ?? $order['fields']['totalAmount']['integerValue'] ?? 0, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($order['fields']['createdAt']['timestampValue'] ?? now())->format('M j, g:i A') }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
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
                            <span class="fw-semibold">{{ count($recentOrders) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Completed Today</span>
                            <span class="fw-semibold text-success">
                                {{ collect($recentOrders)->where('fields.status.stringValue', 'delivered')->count() }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Pending Orders</span>
                            <span class="fw-semibold text-warning">
                                {{ collect($recentOrders)->where('fields.status.stringValue', 'pending')->count() }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Cancelled Orders</span>
                            <span class="fw-semibold text-danger">
                                {{ collect($recentOrders)->where('fields.status.stringValue', 'cancelled')->count() }}
                            </span>
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
                            <h4 class="text-primary mb-1">98.5%</h4>
                            <small class="text-muted">Order Accuracy</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-success mb-1">12 min</h4>
                            <small class="text-muted">Avg Prep Time</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-warning mb-1">${{ number_format(collect($recentOrders)->sum(function($order) {
                                return $order['fields']['totalAmount']['doubleValue'] ?? $order['fields']['totalAmount']['integerValue'] ?? 0;
                            }) / max(count($recentOrders), 1), 2) }}</h4>
                            <small class="text-muted">Avg Order Value</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-info mb-1">{{ count($recentOrders) > 0 ? round(collect($recentOrders)->where('fields.status.stringValue', 'delivered')->count() / count($recentOrders) * 100, 1) : 0 }}%</h4>
                        <small class="text-muted">Success Rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
