@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ \App\Utils\UIStrings::t('reports.title') }}</h1>
        <p class="page-subtitle">Analytics and insights for your business performance</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" id="refreshBtn" onclick="refreshAll()">
            <i class="bi bi-arrow-clockwise me-2"></i>{{ \App\Utils\UIStrings::t('reports.refresh') }}
        </button>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-2"></i>{{ \App\Utils\UIStrings::t('reports.export') }}
            </button>
            <div class="dropdown-menu p-3" style="min-width: 320px;">
                <form method="GET" action="{{ route('reports.export') }}" target="_blank" id="exportForm">
                    <input type="hidden" name="period" id="exportPeriod" value="daily">
                    <input type="hidden" name="branchId" id="exportBranchId" value="">
                    <div class="mb-3">
                        <label class="form-label small">Report Type</label>
                        <select name="report" class="form-select form-select-sm">
                            <option value="sales">{{ \App\Utils\UIStrings::t('reports.sales') }}</option>
                            <option value="top-items">{{ \App\Utils\UIStrings::t('reports.top_items') }}</option>
                            <option value="busy-slots">{{ \App\Utils\UIStrings::t('reports.busy_slots') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Format</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="csv">CSV</option>
                            <option value="xlsx">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100" id="exportBtn">
                        <i class="bi bi-download me-1"></i>Export Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters & Settings</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ \App\Utils\UIStrings::t('reports.period') }}</label>
                <select id="period" class="form-select">
                    <option value="daily">{{ \App\Utils\UIStrings::t('reports.period.daily') }}</option>
                    <option value="weekly">{{ \App\Utils\UIStrings::t('reports.period.weekly') }}</option>
                    <option value="monthly">{{ \App\Utils\UIStrings::t('reports.period.monthly') }}</option>
                </select>
            </div>
            @if(session('role') !== 'branch_admin')
            <div class="col-md-4">
                <label class="form-label">{{ \App\Utils\UIStrings::t('reports.branch') }}</label>
                <select id="branchId" class="form-select">
                    <option value="">{{ \App\Utils\UIStrings::t('reports.all_branches') }}</option>
                    @foreach(($branches ?? []) as $b)
                        <option value="{{ $b['id'] }}" {{ ($currentBranchId ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <input type="date" class="form-control" id="dateRange">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="preconnect" href="https://cdn.jsdelivr.net"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Key Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card primary h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3 class="mb-1 fw-bold" id="totalRevenue">$0</h3>
                <p class="text-muted mb-0">Total Revenue</p>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> <span id="revenueGrowth">+0%</span> from last period
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card success h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3 class="mb-1 fw-bold" id="totalOrders">0</h3>
                <p class="text-muted mb-0">Total Orders</p>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> <span id="ordersGrowth">+0%</span> from last period
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card warning h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="mb-1 fw-bold" id="avgOrderValue">$0</h3>
                <p class="text-muted mb-0">Avg Order Value</p>
                <small class="text-info">
                    <i class="bi bi-graph-up"></i> <span id="aovGrowth">+0%</span> vs target
                </small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card info h-100">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="mb-1 fw-bold" id="totalCustomers">0</h3>
                <p class="text-muted mb-0">Active Customers</p>
                <small class="text-success">
                    <i class="bi bi-arrow-up"></i> <span id="customersGrowth">+0%</span> new customers
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>{{ \App\Utils\UIStrings::t('reports.sales') }}
                </h6>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary active" data-chart-view="revenue">Revenue</button>
                    <button class="btn btn-sm btn-outline-primary" data-chart-view="orders">Orders</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-lg-4">
        <div class="row g-3 h-100">
            <!-- Top Items -->
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-star me-2"></i>{{ \App\Utils\UIStrings::t('reports.top_items') }}
                        </h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                        <div id="topItemsList">
                            <!-- Top items will be loaded here -->
                            <div class="p-3 text-center text-muted">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                Loading top items...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row Charts -->
<div class="row g-4 mt-2">
    <!-- Busy Hours Chart -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-clock me-2"></i>{{ \App\Utils\UIStrings::t('reports.busy_slots') }}
                </h6>
            </div>
            <div class="card-body">
                <canvas id="slotsChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Sales Data Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-table me-2"></i>Sales Summary
                </h6>
                <button class="btn btn-sm btn-outline-primary" onclick="exportTableData()">
                    <i class="bi bi-download me-1"></i>Export
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>{{ \App\Utils\UIStrings::t('reports.table.period') }}</th>
                                <th class="text-end">{{ \App\Utils\UIStrings::t('reports.table.orders') }}</th>
                                <th class="text-end">{{ \App\Utils\UIStrings::t('reports.table.total') }}</th>
                                <th class="text-end">Growth</th>
                            </tr>
                        </thead>
                        <tbody id="salesTable">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    Loading sales data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Loading states and animations
function btnStart(btn, text) {
    if (!btn) return;
    btn.disabled = true;
    btn.dataset._orig = btn.innerHTML;
    btn.innerHTML = `<div class="spinner-border spinner-border-sm me-2"></div>${text || 'Loading...'}`;
}

function btnDone(btn) {
    if (!btn) return;
    btn.disabled = false;
    if (btn.dataset._orig) {
        btn.innerHTML = btn.dataset._orig;
        delete btn.dataset._orig;
    }
}

// Chart instances
let salesChart, itemsChart, slotsChart;
let currentSalesData = [];

// Utility functions
function syncExportInputs() {
    document.getElementById('exportPeriod').value = document.getElementById('period').value;
    document.getElementById('exportBranchId').value = document.getElementById('branchId').value;
}

async function fetchJson(url, params) {
    const qs = new URLSearchParams(params).toString();
    const res = await fetch(url + '?' + qs, { headers: { 'Accept': 'application/json' } });
    return await res.json();
}

function filters() {
    return { 
        period: document.getElementById('period').value, 
        branchId: document.getElementById('branchId').value 
    };
}

function fmt(n) {
    return new Intl.NumberFormat(undefined, { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    }).format(n || 0);
}

function fmtCurrency(n) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD'
    }).format(n || 0);
}

function upsertChart(inst, ctx, type, data, opts) {
    if (inst) {
        inst.data = data;
        inst.options = opts || {};
        inst.update('none');
        return inst;
    }
    return new Chart(ctx, { type, data, options: opts || {} });
}

// Chart configurations
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: '#6366f1',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12
        }
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(148, 163, 184, 0.1)'
            },
            ticks: {
                color: '#64748b'
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(148, 163, 184, 0.1)'
            },
            ticks: {
                color: '#64748b'
            }
        }
    }
};

// Data loading functions
async function loadSales() {
    try {
        const data = await fetchJson("{{ route('reports.sales') }}", filters());
        currentSalesData = data;
        
        // Update metrics
        const totalRevenue = data.reduce((sum, d) => sum + d.total, 0);
        const totalOrders = data.reduce((sum, d) => sum + d.orders, 0);
        const avgOrderValue = totalRevenue / Math.max(totalOrders, 1);
        
        document.getElementById('totalRevenue').textContent = fmtCurrency(totalRevenue);
        document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
        document.getElementById('avgOrderValue').textContent = fmtCurrency(avgOrderValue);
        
        // Update growth indicators (mock data for now)
        document.getElementById('revenueGrowth').textContent = '+12.5%';
        document.getElementById('ordersGrowth').textContent = '+8.3%';
        document.getElementById('aovGrowth').textContent = '+5.2%';
        
        // Chart data
        const labels = data.map(d => d.period);
        const revenues = data.map(d => d.total);
        const orders = data.map(d => d.orders);
        
        const ctx = document.getElementById('salesChart');
        const currentView = document.querySelector('[data-chart-view].active')?.dataset.chartView || 'revenue';
        
        const chartData = currentView === 'revenue' ? revenues : orders;
        const label = currentView === 'revenue' ? 'Revenue' : 'Orders';
        
        salesChart = upsertChart(salesChart, ctx, 'line', {
            labels,
            datasets: [{
                label,
                data: chartData,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        }, {
            ...chartDefaults,
            plugins: {
                ...chartDefaults.plugins,
                tooltip: {
                    ...chartDefaults.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            return currentView === 'revenue' 
                                ? `Revenue: ${fmtCurrency(context.parsed.y)}`
                                : `Orders: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            }
        });
        
        // Update table
        updateSalesTable(data);
        
    } catch (error) {
        console.error('Error loading sales data:', error);
        showErrorMessage('Failed to load sales data');
    }
}

async function loadTopItems() {
    try {
        const f = filters();
        f.limit = 10;
        const data = await fetchJson("{{ route('reports.top_items') }}", f);
        
        // Update top items list
        const listContainer = document.getElementById('topItemsList');
        if (data.length === 0) {
            listContainer.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i class="bi bi-info-circle mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-0">No items data available for this period</p>
                </div>
            `;
        } else {
            listContainer.innerHTML = data.map((item, index) => `
                <div class="d-flex align-items-center p-3 border-bottom">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-size: 12px; font-weight: bold;">
                        ${index + 1}
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <small class="text-muted">${item.qty} sold</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">${fmtCurrency(item.revenue || item.qty * 15)}</div>
                        <small class="text-muted">Revenue</small>
                    </div>
                </div>
            `).join('');
        }
        
    } catch (error) {
        console.error('Error loading top items:', error);
        document.getElementById('topItemsList').innerHTML = `
            <div class="p-3 text-center text-danger">
                <i class="bi bi-exclamation-triangle mb-2"></i>
                <p class="mb-0">Failed to load top items</p>
            </div>
        `;
    }
}

async function loadBusySlots() {
    try {
        const data = await fetchJson("{{ route('reports.busy_slots') }}", filters());
        const labels = data.map(d => d.hour);
        const counts = data.map(d => d.orders);
        
        const ctx = document.getElementById('slotsChart');
        slotsChart = upsertChart(slotsChart, ctx, 'bar', {
            labels,
            datasets: [{
                label: 'Orders',
                data: counts,
                backgroundColor: 'rgba(245, 158, 11, 0.8)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        }, {
            ...chartDefaults,
            plugins: {
                ...chartDefaults.plugins,
                tooltip: {
                    ...chartDefaults.plugins.tooltip,
                    callbacks: {
                        label: function(context) {
                            return `Orders: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            }
        });
        
    } catch (error) {
        console.error('Error loading busy slots:', error);
        showErrorMessage('Failed to load busy hours data');
    }
}

function updateSalesTable(data) {
    const tbody = document.getElementById('salesTable');
    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    No sales data available for this period
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = data.map((d, index) => {
        const prevItem = data[index + 1];
        let growth = 0;
        let growthClass = 'text-muted';
        let growthIcon = '';
        
        if (prevItem && prevItem.total > 0) {
            growth = ((d.total - prevItem.total) / prevItem.total) * 100;
            if (growth > 0) {
                growthClass = 'text-success';
                growthIcon = '<i class="bi bi-arrow-up me-1"></i>';
            } else if (growth < 0) {
                growthClass = 'text-danger';
                growthIcon = '<i class="bi bi-arrow-down me-1"></i>';
            }
        }
        
        return `
            <tr>
                <td class="fw-medium">${d.period}</td>
                <td class="text-end">${d.orders.toLocaleString()}</td>
                <td class="text-end fw-semibold">${fmtCurrency(d.total)}</td>
                <td class="text-end">
                    <span class="${growthClass} small">
                        ${growthIcon}${growth !== 0 ? Math.abs(growth).toFixed(1) + '%' : '-'}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function showErrorMessage(message) {
    // You could implement a toast notification here
    console.error(message);
}

async function refreshAll() {
    syncExportInputs();
    const btn = document.getElementById('refreshBtn');
    btnStart(btn, 'Refreshing...');
    
    try {
        await Promise.all([
            loadSales(),
            loadTopItems(),
            loadBusySlots()
        ]);
    } catch (error) {
        showErrorMessage('Failed to refresh data');
    } finally {
        btnDone(btn);
    }
}

function resetFilters() {
    document.getElementById('period').value = 'daily';
    document.getElementById('branchId').value = '';
    document.getElementById('dateRange').value = '';
    refreshAll();
}

function exportTableData() {
    const data = currentSalesData;
    const csv = [
        ['Period', 'Orders', 'Revenue'],
        ...data.map(d => [d.period, d.orders, d.total])
    ].map(row => row.join(',')).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sales-summary.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Event listeners
document.getElementById('period').addEventListener('change', refreshAll);
document.getElementById('branchId').addEventListener('change', refreshAll);

// Chart view toggles
document.querySelectorAll('[data-chart-view]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('[data-chart-view]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        loadSales(); // Refresh chart with new view
    });
});

// Export form handling
document.getElementById('exportForm').addEventListener('submit', function() {
    btnStart(document.getElementById('exportBtn'), 'Exporting...');
    setTimeout(() => btnDone(document.getElementById('exportBtn')), 2000);
});

// Mock customer data update
function updateCustomerMetrics() {
    document.getElementById('totalCustomers').textContent = '1,247';
    document.getElementById('customersGrowth').textContent = '+15.8%';
}

// Initialize
syncExportInputs();
updateCustomerMetrics();
refreshAll();
</script>
@endsection
