@php
    $role = $role ?? session('role');
    // Precompute a customer id -> name map if a $customers array is passed to the view
    $customerMap = collect($customers ?? [])->mapWithKeys(function($c){
        $id = $c['id'] ?? ($c['userId'] ?? null);
        $name = $c['name'] ?? ($c['full_name'] ?? ($c['displayName'] ?? ($c['email'] ?? null)));
        return $id ? [$id => $name] : [];
    })->toArray();
@endphp
@extends('layouts.admin')
@section('content')
<div class="page-header mb-4">
    <h1>Orders</h1>
    <p class="text-muted">View and manage all customer orders</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="ordersFilterForm" class="row g-3">
            @if($role === 'admin')
            <div class="col-md-3">
                <label class="form-label">Restaurant</label>
                <select name="restaurantId" id="filterRestaurant" class="form-select filter-select">
                    <option value="">All</option>
                    @foreach($restaurants as $r)
                        <option value="{{ $r['id'] }}" {{ ($currentRestaurantId ?? '') === $r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branchId" id="filterBranch" class="form-select filter-select">
                    <option value="">All</option>
                    @foreach($branches as $b)
                        <option value="{{ $b['id'] }}" {{ ($currentBranchId ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" id="filterStatus" class="form-select filter-select">
                    <option value="">All</option>
                    <option value="pending" {{ (isset($filterStatus) && $filterStatus==='pending') ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ (isset($filterStatus) && $filterStatus==='confirmed') ? 'selected' : '' }}>Confirmed</option>
                    <option value="preparing" {{ (isset($filterStatus) && $filterStatus==='preparing') ? 'selected' : '' }}>Preparing</option>
                    <option value="ready" {{ (isset($filterStatus) && $filterStatus==='ready') ? 'selected' : '' }}>Ready</option>
                    <option value="delivered" {{ (isset($filterStatus) && $filterStatus==='delivered') ? 'selected' : '' }}>Delivered</option>
                    <option value="completed" {{ (isset($filterStatus) && $filterStatus==='completed') ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ (isset($filterStatus) && $filterStatus==='cancelled') ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Order Type</label>
                <select name="orderType" id="filterOrderType" class="form-select filter-select">
                    <option value="">All</option>
                    <option value="pickup" {{ (isset($filterOrderType) && $filterOrderType==='pickup') ? 'selected' : '' }}>Pickup</option>
                    <option value="delivery" {{ (isset($filterOrderType) && $filterOrderType==='delivery') ? 'selected' : '' }}>Delivery</option>
                    <option value="dine-in" {{ (isset($filterOrderType) && $filterOrderType==='dine-in') ? 'selected' : '' }}>Dine-in</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div id="orderStats">
@if(!empty($orders))
<div class="row g-3 mb-4">
    @php
        $statusCounts = collect($orders)->countBy('status');
        $totalRevenue = collect($orders)->sum('totalAmount');
        $activeCount = ($statusCounts->get('pending', 0) + $statusCounts->get('confirmed', 0) + $statusCounts->get('preparing', 0) + $statusCounts->get('ready', 0));
        $completedCount = ($statusCounts->get('delivered', 0) + $statusCounts->get('completed', 0));
    @endphp
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-receipt text-primary" style="font-size: 2rem;"></i>
                <h4 class="mb-1 mt-2">{{ count($orders) }}</h4>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                <h4 class="mb-1 mt-2">{{ $activeCount }}</h4>
                <p class="text-muted mb-0">Active Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                <h4 class="mb-1 mt-2">{{ $completedCount }}</h4>
                <p class="text-muted mb-0">Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar text-info" style="font-size: 2rem;"></i>
                <h4 class="mb-1 mt-2">€{{ number_format($totalRevenue, 2) }}</h4>
                <p class="text-muted mb-0">Revenue</p>
            </div>
        </div>
    </div>
</div>
@endif
</div>

<div class="card" id="ordersTableCard">
    <div class="card-body">
        @if(empty($orders))
            <div class="text-center py-5">
                <i class="bi bi-receipt text-muted mb-3" style="font-size: 4rem;"></i>
                <h5 class="text-muted mb-3">No Orders Found</h5>
                <p class="text-muted">No orders have been placed yet. Orders will appear here when customers start ordering.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Delivery</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                            <th>Created</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $safeId = preg_replace('/[^A-Za-z0-9\\-_]/', '-', $order['id']);
                                $branchObj = collect($branches ?? [])->firstWhere('id', $order['branchId']);
                                $branchName = $branchObj['name'] ?? ($order['branchName'] ?? 'Unknown');
                            @endphp
                            <tr>
                                <td>
                                    <strong class="text-primary">{{ $order['displayId'] }}</strong>
                                </td>
                                <td>
                                    {{ $branchName }}
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'preparing' => 'primary',
                                            'ready' => 'success',
                                            'delivered' => 'success',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                        ];
                                        $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">{{ ucfirst($order['status']) }}</span>
                                </td>
                                <td>
                                    @php
                                        $paymentColors = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'failed' => 'danger',
                                        ];
                                        $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $paymentColor }}">{{ ucfirst($order['paymentStatus']) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-{{ $order['orderType'] === 'delivery' ? 'truck' : ($order['orderType'] === 'pickup' ? 'bag' : 'shop') }}"></i>
                                        {{ ucfirst($order['orderType']) }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#items-{{ $safeId }}" aria-expanded="false" aria-controls="items-{{ $safeId }}">
                                        <i class="bi bi-list"></i> {{ count($order['items']) }} item(s)
                                    </button>
                                </td>
                                <td class="text-end">€{{ number_format($order['subtotal'], 2) }}</td>
                                <td class="text-end">€{{ number_format($order['deliveryFee'], 2) }}</td>
                                <td class="text-end">€{{ number_format($order['taxAmount'], 2) }}</td>
                                <td class="text-end"><strong>€{{ number_format($order['totalAmount'], 2) }}</strong></td>
                                <td style="white-space:nowrap">
                                    @if($order['createdAt'])
                                        {{ \Carbon\Carbon::parse($order['createdAt'])->format('Y-m-d H:i') }}
                                        <div class="text-muted small">{{ \Carbon\Carbon::parse($order['createdAt'])->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" title="View Details" data-bs-toggle="modal" data-bs-target="#orderModal-{{ $safeId }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Items Collapse Row -->
                            <tr class="collapse" id="items-{{ $safeId }}">
                                <td colspan="12" class="bg-light">
                                    <div class="p-3">
                                        <h6 class="mb-3">Order Items:</h6>
                                        @foreach($order['items'] as $item)
                                            <div class="card mb-2">
                                                <div class="card-body p-2">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-2">
                                                            @if($item['imageUrl'])
                                                                <img src="{{ $item['imageUrl'] }}" alt="{{ $item['name'] }}" class="img-fluid rounded" style="max-height: 60px;">
                                                            @else
                                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 60px;">
                                                                    <i class="bi bi-image text-white"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-3">
                                                            <strong>{{ $item['name'] }}</strong>
                                                            @if($item['nameLocalized'] !== $item['name'])
                                                                <div class="text-muted small">{{ $item['nameLocalized'] }}</div>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>Size:</strong> {{ $item['size'] }}<br>
                                                            <strong>Base:</strong> {{ $item['base'] }}
                                                        </div>
                                                        <div class="col-md-3">
                                                            @if(!empty($item['Green']))
                                                                <div class="mb-1">
                                                                    <strong>Green:</strong>
                                                                    @foreach($item['Green'] as $green)
                                                                        <span class="badge bg-success me-1">{{ $green['name'] }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            @if(!empty($item['Topping']))
                                                                <div>
                                                                    <strong>Toppings:</strong>
                                                                    @foreach($item['Topping'] as $topping)
                                                                        <span class="badge bg-warning me-1">{{ $topping['name'] }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="col-md-2 text-end">
                                                            <div><strong>Qty:</strong> {{ $item['quantity'] }}</div>
                                                            <div><strong>Price:</strong> €{{ number_format($item['price'], 2) }}</div>
                                                            @if($item['customizationExtra'] > 0)
                                                                <div class="text-muted small">+€{{ number_format($item['customizationExtra'], 2) }} extra</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Render modals outside the table to avoid invalid HTML nesting --}}
            @push('modals')
            @foreach($orders as $order)
                @php
                    $safeId = preg_replace('/[^A-Za-z0-9\\-_]/', '-', $order['id']);
                    $statusColors = [
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'primary',
                        'ready' => 'success',
                        'delivered' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $statusColor = $statusColors[$order['status']] ?? 'secondary';
                    $paymentColors = [
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                    ];
                    $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'secondary';
                    $branchObj = collect($branches ?? [])->firstWhere('id', $order['branchId']);
                    $branchName = $branchObj['name'] ?? ($order['branchName'] ?? 'Unknown');
                    $customerName = $order['customerName'] ?? ($customerMap[$order['customerId']] ?? ($order['customerId'] ? $order['customerId'] : 'Unknown'));
                @endphp
                <div class="modal fade" id="orderModal-{{ $safeId }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Order Details - {{ $order['displayId'] }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Display ID:</strong> {{ $order['displayId'] }}</p>
                                        <p><strong>Customer:</strong> {{ $customerName }}</p>
                                        <p><strong>Branch:</strong> {{ $branchName }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> <span class="badge bg-{{ $statusColor }}">{{ ucfirst($order['status']) }}</span></p>
                                        <p><strong>Payment:</strong> <span class="badge bg-{{ $paymentColor }}">{{ ucfirst($order['paymentStatus']) }}</span></p>
                                        <p><strong>Order Type:</strong> {{ ucfirst($order['orderType']) }}</p>
                                        <p><strong>Created:</strong> {{ $order['createdAt'] ? \Carbon\Carbon::parse($order['createdAt'])->format('Y-m-d H:i:s') : 'N/A' }}</p>
                                    </div>
                                </div>
                                <hr>
                                <h6>Order Summary</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-end">€{{ number_format($order['subtotal'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Delivery Fee:</td>
                                        <td class="text-end">€{{ number_format($order['deliveryFee'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Discount:</td>
                                        <td class="text-end">-€{{ number_format($order['discount'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tax:</td>
                                        <td class="text-end">€{{ number_format($order['taxAmount'], 2) }}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Total Amount:</td>
                                        <td class="text-end">€{{ number_format($order['totalAmount'], 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            @endpush
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('.filter-select');
    const branches = @json($branches ?? []);
    let currentPage = {{ data_get($pagination, 'page', 1) }};
    let currentPerPage = {{ data_get($pagination, 'perPage', 20) }};
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            loadOrders(1);
        });
    });

    // Load initial page of orders (so pagination controls render)
    loadOrders(currentPage);
    
    function loadOrders(page = 1) {
        const restaurantId = document.getElementById('filterRestaurant')?.value || '';
        const branchId = document.getElementById('filterBranch')?.value || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const orderType = document.getElementById('filterOrderType')?.value || '';
        
        const params = new URLSearchParams();
        if (restaurantId) params.append('restaurantId', restaurantId);
        if (branchId) params.append('branchId', branchId);
        if (status) params.append('status', status);
        if (orderType) params.append('orderType', orderType);
        params.append('page', page);
        params.append('perPage', currentPerPage);
        
        fetch(`{{ route('orders.ajax') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                currentPage = data.pagination?.page || page;
                updateOrdersDisplay(data.orders, data.customers, data.pagination);
            })
            .catch(error => {
                console.error('Error loading orders:', error);
            });
    }
    
    function updateOrdersDisplay(orders, customers, pagination) {
        // Build customer map
        const customerMap = {};
        customers.forEach(c => {
            if (c.id) {
                customerMap[c.id] = c.name || c.id;
            }
        });
        
        // Update stats
        updateStats(orders);
        
        // Update table
        updateTable(orders, customerMap);
        
        // Update modals
        updateModals(orders, customerMap);

        // Render pagination controls
        renderPagination(pagination || {page: currentPage, perPage: currentPerPage, total: 0, lastPage: 1});
    }
    
    function updateStats(orders) {
        if (orders.length === 0) {
            document.getElementById('orderStats').innerHTML = '';
            return;
        }
        
        const statusCounts = {};
        let totalRevenue = 0;
        
        orders.forEach(order => {
            statusCounts[order.status] = (statusCounts[order.status] || 0) + 1;
            totalRevenue += parseFloat(order.totalAmount || 0);
        });
        
        const activeCount = (statusCounts.pending || 0) + (statusCounts.confirmed || 0) + 
                           (statusCounts.preparing || 0) + (statusCounts.ready || 0);
        const completedCount = (statusCounts.delivered || 0) + (statusCounts.completed || 0);
        
        document.getElementById('orderStats').innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-receipt text-primary" style="font-size: 2rem;"></i>
                            <h4 class="mb-1 mt-2">${orders.length}</h4>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                            <h4 class="mb-1 mt-2">${activeCount}</h4>
                            <p class="text-muted mb-0">Active Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h4 class="mb-1 mt-2">${completedCount}</h4>
                            <p class="text-muted mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar text-info" style="font-size: 2rem;"></i>
                            <h4 class="mb-1 mt-2">$${totalRevenue.toFixed(2)}</h4>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function renderPagination(pagination) {
        const cardBody = document.querySelector('#ordersTableCard .card-body');
        const navId = 'orders-pagination';
        // remove old pagination if exists
        const old = document.getElementById(navId);
        if (old) old.remove();

        if (!pagination || pagination.total <= pagination.perPage) return;

        const nav = document.createElement('nav');
        nav.id = navId;
        nav.className = 'mt-3';

        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-end mb-0';

        const makePageItem = (p, label = null, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label || p;
            a.addEventListener('click', (e) => { e.preventDefault(); if (!disabled) { loadOrders(p); } });
            li.appendChild(a);
            return li;
        };

        // Prev
        ul.appendChild(makePageItem(Math.max(1, pagination.page - 1), '«', false, pagination.page <= 1));

        // show up to 7 pages around current
        const start = Math.max(1, pagination.page - 3);
        const end = Math.min(pagination.lastPage, pagination.page + 3);
        if (start > 1) {
            ul.appendChild(makePageItem(1, '1'));
            if (start > 2) {
                const gap = document.createElement('li'); gap.className = 'page-item disabled'; gap.innerHTML = '<span class="page-link">…</span>'; ul.appendChild(gap);
            }
        }
        for (let p = start; p <= end; p++) {
            ul.appendChild(makePageItem(p, null, p === pagination.page));
        }
        if (end < pagination.lastPage) {
            if (end < pagination.lastPage - 1) {
                const gap = document.createElement('li'); gap.className = 'page-item disabled'; gap.innerHTML = '<span class="page-link">…</span>'; ul.appendChild(gap);
            }
            ul.appendChild(makePageItem(pagination.lastPage, String(pagination.lastPage)));
        }

        // Next
        ul.appendChild(makePageItem(Math.min(pagination.lastPage, pagination.page + 1), '»', false, pagination.page >= pagination.lastPage));

        nav.appendChild(ul);
        cardBody.appendChild(nav);
    }
    
    function updateTable(orders, customerMap) {
        const cardBody = document.querySelector('#ordersTableCard .card-body');
        
        if (orders.length === 0) {
            cardBody.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted mb-3" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mb-3">No Orders Found</h5>
                    <p class="text-muted">No orders match the selected filters.</p>
                </div>
            `;
            return;
        }
        
        let tableHtml = `
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Delivery</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                            <th>Created</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        orders.forEach(order => {
            const safeId = order.id.replace(/[^A-Za-z0-9\-_]/g, '-');
            const branchName = getBranchName(order.branchId);
            const statusColor = getStatusColor(order.status);
            const paymentColor = getPaymentColor(order.paymentStatus);
            const orderTypeIcon = getOrderTypeIcon(order.orderType);
            
            tableHtml += `
                <tr>
                    <td><strong class="text-primary">${order.displayId}</strong></td>
                    <td>${branchName}</td>
                    <td><span class="badge bg-${statusColor}">${capitalize(order.status)}</span></td>
                    <td><span class="badge bg-${paymentColor}">${capitalize(order.paymentStatus)}</span></td>
                    <td><span class="badge bg-light text-dark"><i class="bi bi-${orderTypeIcon}"></i> ${capitalize(order.orderType)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#items-${safeId}" aria-expanded="false" aria-controls="items-${safeId}">
                            <i class="bi bi-list"></i> ${order.items.length} item(s)
                        </button>
                    </td>
                    <td class="text-end">$${parseFloat(order.subtotal).toFixed(2)}</td>
                    <td class="text-end">$${parseFloat(order.deliveryFee).toFixed(2)}</td>
                    <td class="text-end">$${parseFloat(order.taxAmount).toFixed(2)}</td>
                    <td class="text-end"><strong>$${parseFloat(order.totalAmount).toFixed(2)}</strong></td>
                    <td style="white-space:nowrap">${formatDate(order.createdAt)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" title="View Details" data-bs-toggle="modal" data-bs-target="#orderModal-${safeId}">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr class="collapse" id="items-${safeId}">
                    <td colspan="12" class="bg-light">
                        ${renderItems(order.items)}
                    </td>
                </tr>
            `;
        });
        
        tableHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        cardBody.innerHTML = tableHtml;
    }
    
    function updateModals(orders, customerMap) {
        // Remove existing modals
        const existingModals = document.querySelectorAll('[id^="orderModal-"]');
        existingModals.forEach(modal => modal.remove());
        
        // Create new modals
        orders.forEach(order => {
            const safeId = order.id.replace(/[^A-Za-z0-9\-_]/g, '-');
            const branchName = getBranchName(order.branchId);
            const customerName = customerMap[order.customerId] || order.customerId || 'Unknown';
            const statusColor = getStatusColor(order.status);
            const paymentColor = getPaymentColor(order.paymentStatus);
            
            const modalHtml = `
                <div class="modal fade" id="orderModal-${safeId}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Order Details - ${order.displayId}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Display ID:</strong> ${order.displayId}</p>
                                        <p><strong>Customer:</strong> ${customerName}</p>
                                        <p><strong>Branch:</strong> ${branchName}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> <span class="badge bg-${statusColor}">${capitalize(order.status)}</span></p>
                                        <p><strong>Payment:</strong> <span class="badge bg-${paymentColor}">${capitalize(order.paymentStatus)}</span></p>
                                        <p><strong>Order Type:</strong> ${capitalize(order.orderType)}</p>
                                        <p><strong>Created:</strong> ${formatDate(order.createdAt)}</p>
                                    </div>
                                </div>
                                <hr>
                                <h6>Order Summary</h6>
                                <table class="table table-sm">
                                    <tr><td>Subtotal:</td><td class="text-end">$${parseFloat(order.subtotal).toFixed(2)}</td></tr>
                                    <tr><td>Delivery Fee:</td><td class="text-end">$${parseFloat(order.deliveryFee).toFixed(2)}</td></tr>
                                    <tr><td>Discount:</td><td class="text-end">-$${parseFloat(order.discount || 0).toFixed(2)}</td></tr>
                                    <tr><td>Tax:</td><td class="text-end">$${parseFloat(order.taxAmount).toFixed(2)}</td></tr>
                                    <tr class="fw-bold"><td>Total Amount:</td><td class="text-end">$${parseFloat(order.totalAmount).toFixed(2)}</td></tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        });
    }
    
    function renderItems(items) {
        let html = '<div class="p-3"><h6 class="mb-3">Order Items:</h6>';
        
        items.forEach(item => {
            html += `
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                ${item.imageUrl ? 
                                    `<img src="${item.imageUrl}" alt="${item.name}" class="img-fluid rounded" style="max-height: 60px;">` :
                                    `<div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 60px;"><i class="bi bi-image text-white"></i></div>`
                                }
                            </div>
                            <div class="col-md-3">
                                <strong>${item.name}</strong>
                                ${item.nameLocalized !== item.name ? `<div class="text-muted small">${item.nameLocalized}</div>` : ''}
                            </div>
                            <div class="col-md-2">
                                <strong>Size:</strong> ${item.size}<br>
                                <strong>Base:</strong> ${item.base}
                            </div>
                            <div class="col-md-3">
                                ${renderCustomizations(item)}
                            </div>
                            <div class="col-md-2 text-end">
                                <div><strong>Qty:</strong> ${item.quantity}</div>
                                <div><strong>Price:</strong> $${parseFloat(item.price).toFixed(2)}</div>
                                ${item.customizationExtra > 0 ? `<div class="text-muted small">+$${parseFloat(item.customizationExtra).toFixed(2)} extra</div>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    function renderCustomizations(item) {
        let html = '';
        
        if (item.Green && item.Green.length > 0) {
            html += '<div class="mb-1"><strong>Green:</strong> ';
            item.Green.forEach(green => {
                html += `<span class="badge bg-success me-1">${green.name}</span>`;
            });
            html += '</div>';
        }
        
        if (item.Topping && item.Topping.length > 0) {
            html += '<div><strong>Toppings:</strong> ';
            item.Topping.forEach(topping => {
                html += `<span class="badge bg-warning me-1">${topping.name}</span>`;
            });
            html += '</div>';
        }
        
        return html;
    }
    
    function getBranchName(branchId) {
        const branch = branches.find(b => b.id === branchId);
        return branch ? branch.name : 'Unknown';
    }
    
    function getStatusColor(status) {
        const colors = {
            pending: 'warning',
            confirmed: 'info',
            preparing: 'primary',
            ready: 'success',
            delivered: 'success',
            completed: 'success',
            cancelled: 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function getPaymentColor(status) {
        const colors = {
            pending: 'warning',
            paid: 'success',
            failed: 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function getOrderTypeIcon(type) {
        const icons = {
            delivery: 'truck',
            pickup: 'bag',
            'dine-in': 'shop'
        };
        return icons[type] || 'receipt';
    }
    
    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        const formatted = date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        let diffStr = '';
        
        if (diff < 60) diffStr = `${diff} seconds ago`;
        else if (diff < 3600) diffStr = `${Math.floor(diff / 60)} minutes ago`;
        else if (diff < 86400) diffStr = `${Math.floor(diff / 3600)} hours ago`;
        else diffStr = `${Math.floor(diff / 86400)} days ago`;
        
        return `${formatted}<div class="text-muted small">${diffStr}</div>`;
    }
});
</script>
@endsection
