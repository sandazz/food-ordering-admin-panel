@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ \App\Utils\UIStrings::t('orders.title') }}</h1>
        <p class="page-subtitle">Manage and track all customer orders</p>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-funnel me-2"></i>Filter Orders
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#"><i class="bi bi-clock me-2"></i>Pending</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-check-circle me-2"></i>Confirmed</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-truck me-2"></i>In Progress</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-check-all me-2"></i>Completed</a></li>
            </ul>
        </div>
        <button class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>New Order
        </button>
    </div>
</div>

@if(($mode ?? 'single') === 'single')
    @if(empty($orders))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-receipt text-muted mb-3" style="font-size: 4rem;"></i>
                <h5 class="text-muted mb-3">{{ \App\Utils\UIStrings::t('orders.none') }}</h5>
                <p class="text-muted mb-4">No orders have been placed yet. Orders will appear here when customers start ordering.</p>
                <a href="{{ url('/admin/menu') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add Menu Items
                </a>
            </div>
        </div>
    @else
        <!-- Orders Summary Cards -->
        <div class="row g-3 mb-4">
            @php
                $statusCounts = collect($orders)->countBy('status');
                $totalRevenue = collect($orders)->sum('totalAmount');
            @endphp
            <div class="col-md-3">
                <div class="card stats-card primary">
                    <div class="card-body text-center">
                        <div class="icon mx-auto">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h4 class="mb-1">{{ count($orders) }}</h4>
                        <p class="text-muted mb-0">Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card warning">
                    <div class="card-body text-center">
                        <div class="icon mx-auto">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h4 class="mb-1">{{ $statusCounts->get('pending', 0) + $statusCounts->get('confirmed', 0) + $statusCounts->get('preparing', 0) }}</h4>
                        <p class="text-muted mb-0">Active Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card success">
                    <div class="card-body text-center">
                        <div class="icon mx-auto">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h4 class="mb-1">{{ $statusCounts->get('delivered', 0) + $statusCounts->get('completed', 0) }}</h4>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card info">
                    <div class="card-body text-center">
                        <div class="icon mx-auto">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h4 class="mb-1">${{ number_format($totalRevenue, 0) }}</h4>
                        <p class="text-muted mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">All Orders</h6>
                <div class="d-flex gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search orders...">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
                                <th>Customer</th>
                                <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
                                <th>{{ \App\Utils\UIStrings::t('orders.payment') }}</th>
                                <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
                                <th>{{ \App\Utils\UIStrings::t('orders.type') }}</th>
                                <th>Time</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $o)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-primary">#{{ substr($o['id'], -8) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                {{ strtoupper(substr($o['customerName'] ?? 'Guest', 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $o['customerName'] ?? 'Guest Customer' }}</div>
                                                <small class="text-muted">{{ $o['customerEmail'] ?? 'No email' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $status = strtolower($o['status']);
                                            $statusClass = 'status-' . $status;
                                            $statusIcon = match($status) {
                                                'pending' => 'clock',
                                                'confirmed' => 'check-circle',
                                                'preparing' => 'arrow-repeat',
                                                'ready' => 'check-all',
                                                'delivered' => 'truck',
                                                'cancelled' => 'x-circle',
                                                default => 'question-circle'
                                            };
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">
                                            <i class="bi bi-{{ $statusIcon }} me-1"></i>{{ ucfirst($o['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $paymentStatus = strtolower($o['paymentStatus'] ?? 'pending');
                                            $paymentClass = $paymentStatus === 'paid' ? 'text-success' : ($paymentStatus === 'failed' ? 'text-danger' : 'text-warning');
                                            $paymentIcon = $paymentStatus === 'paid' ? 'check-circle-fill' : ($paymentStatus === 'failed' ? 'x-circle-fill' : 'clock-fill');
                                        @endphp
                                        <span class="{{ $paymentClass }}">
                                            <i class="bi bi-{{ $paymentIcon }} me-1"></i>{{ ucfirst($o['paymentStatus'] ?? 'Pending') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">${{ number_format($o['totalAmount'], 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-{{ $o['orderType'] === 'delivery' ? 'truck' : 'shop' }} me-1"></i>
                                            {{ ucfirst($o['orderType']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ \Carbon\Carbon::parse($o['createdAt'] ?? now())->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View Details" data-bs-toggle="modal" data-bs-target="#orderModal{{ $loop->index }}">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" title="Update Status">
                                                <i class="bi bi-arrow-up-circle"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-printer me-2"></i>Print</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Cancel</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@else
    @if(empty($branchOrders))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-building text-muted mb-3" style="font-size: 4rem;"></i>
                <h5 class="text-muted mb-3">{{ \App\Utils\UIStrings::t('orders.none') }}</h5>
                <p class="text-muted">No orders found for any branches.</p>
            </div>
        </div>
    @else
        @foreach($branchOrders as $bo)
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-building me-2"></i>{{ \App\Utils\UIStrings::t('branch') }}: {{ $bo['branch']['name'] }}
                        </h6>
                        <span class="badge bg-primary">{{ count($bo['orders']) }} orders</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(empty($bo['orders']))
                        <div class="text-center py-4">
                            <span class="text-muted">{{ \App\Utils\UIStrings::t('orders.none') }}</span>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
                                        <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
                                        <th>{{ \App\Utils\UIStrings::t('orders.payment') }}</th>
                                        <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
                                        <th>{{ \App\Utils\UIStrings::t('orders.type') }}</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bo['orders'] as $o)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold text-primary">#{{ substr($o['id'], -8) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $status = strtolower($o['status']);
                                                    $statusClass = 'status-' . $status;
                                                @endphp
                                                <span class="status-badge {{ $statusClass }}">{{ ucfirst($o['status']) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $paymentStatus = strtolower($o['paymentStatus'] ?? 'pending');
                                                    $paymentClass = $paymentStatus === 'paid' ? 'text-success' : 'text-warning';
                                                @endphp
                                                <span class="{{ $paymentClass }}">{{ ucfirst($o['paymentStatus'] ?? 'Pending') }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-semibold">${{ number_format($o['totalAmount'], 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ ucfirst($o['orderType']) }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="View">
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
                    @endif
                </div>
            </div>
        @endforeach
    @endif
@endif
@endsection
