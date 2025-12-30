@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-credit-card"></i> Payment History
            </h1>
            <p class="text-muted mb-0">View and analyze payment transaction history</p>
        </div>
        <div>
            <a href="{{ url('/admin/payment-history?export=csv') }}" class="btn btn-outline-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    @if(!empty($errors))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card primary h-100">
                <div class="card-body">
                    <div class="icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h6 class="text-muted mb-1">Total Revenue</h6>
                    <h3 class="mb-0">€{{ number_format($stats['total_revenue'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card success h-100">
                <div class="card-body">
                    <div class="icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h6 class="text-muted mb-1">Successful Payments</h6>
                    <h3 class="mb-0">{{ $stats['success_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card warning h-100">
                <div class="card-body">
                    <div class="icon">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <h6 class="text-muted mb-1">Failed Payments</h6>
                    <h3 class="mb-0">{{ $stats['failed_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card info h-100">
                <div class="card-body">
                    <div class="icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h6 class="text-muted mb-1">Total Transactions</h6>
                    <h3 class="mb-0">{{ $stats['total_count'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Revenue Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ url('/admin/payment-history') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] }}">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All</option>
                            <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="failed" {{ $filters['status'] === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    @if(in_array($role, ['admin', 'restaurant_admin']))
                    <div class="col-md-3">
                        <label for="branchId" class="form-label">Branch</label>
                        <select class="form-select" id="branchId" name="branchId">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch['id'] }}" {{ $filters['branchId'] === $branch['id'] ? 'selected' : '' }}>
                                    {{ $branch['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="{{ url('/admin/payment-history') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table"></i> Transactions</h5>
            <span class="badge bg-primary">{{ $pagination['total'] }} total</span>
        </div>
        <div class="card-body p-0">
            @if(count($transactions) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Order ID</th>
                                @if(in_array($role, ['admin', 'restaurant_admin']))
                                <th>Branch</th>
                                @endif
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Customer Email</th>
                                <th>Payment Method</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                            <tr>
                                <td>
                                    <code class="text-primary">{{ $transaction['transaction_id'] }}</code>
                                </td>
                                <td>
                                    <strong>{{ $transaction['order_display_id'] ?? $transaction['order_reference'] ?? 'N/A' }}</strong>
                                </td>
                                @if(in_array($role, ['admin', 'restaurant_admin']))
                                <td>
                                    <span class="badge bg-secondary">{{ $transaction['branch_name'] }}</span>
                                </td>
                                @endif
                                <td class="fw-bold">€{{ number_format($transaction['amount'], 2) }}</td>
                                <td>
                                    @php
                                        $statusClass = match($transaction['status']) {
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                            'cancelled' => 'warning',
                                            'pending' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst($transaction['status']) }}
                                    </span>
                                </td>
                                <td>{{ $transaction['customer_email'] ?? 'N/A' }}</td>
                                <td>{{ $transaction['payment_method'] }}</td>
                                <td>
                                    <small>{{ date('Y-m-d H:i', strtotime($transaction['timestamp'])) }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ ($pagination['page'] - 1) * $pagination['perPage'] + 1 }} 
                            to {{ min($pagination['page'] * $pagination['perPage'], $pagination['total']) }} 
                            of {{ $pagination['total'] }} transactions
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                @if($pagination['page'] > 1)
                                <li class="page-item">
                                    <a class="page-link" href="?page={{ $pagination['page'] - 1 }}&from={{ $filters['from'] }}&to={{ $filters['to'] }}&status={{ $filters['status'] }}&branchId={{ $filters['branchId'] }}">
                                        Previous
                                    </a>
                                </li>
                                @endif

                                @for($i = max(1, $pagination['page'] - 2); $i <= min($pagination['lastPage'], $pagination['page'] + 2); $i++)
                                <li class="page-item {{ $i === $pagination['page'] ? 'active' : '' }}">
                                    <a class="page-link" href="?page={{ $i }}&from={{ $filters['from'] }}&to={{ $filters['to'] }}&status={{ $filters['status'] }}&branchId={{ $filters['branchId'] }}">
                                        {{ $i }}
                                    </a>
                                </li>
                                @endfor

                                @if($pagination['page'] < $pagination['lastPage'])
                                <li class="page-item">
                                    <a class="page-link" href="?page={{ $pagination['page'] + 1 }}&from={{ $filters['from'] }}&to={{ $filters['to'] }}&status={{ $filters['status'] }}&branchId={{ $filters['branchId'] }}">
                                        Next
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">No payment history available for the selected period.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueData = @json($revenueByDate);
    const labels = Object.keys(revenueData);
    const data = Object.values(revenueData);

    const ctx = document.getElementById('revenueChart');
    if (ctx && labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (EUR)',
                    data: data,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: €' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
