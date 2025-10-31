@extends('layouts.admin')
@section('content')
<h2>Reports & Analytics</h2>

<link rel="preconnect" href="https://cdn.jsdelivr.net"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div style="display:grid;gap:16px;">
  <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;background:#f9fafb;padding:12px;border:1px solid #e5e7eb;border-radius:8px;">
    <div>
      <label>Period<br>
        <select id="period" style="min-width:160px;">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </label>
    </div>
    <div>
      <label>Branch<br>
        <select id="branchId" style="min-width:200px;">
          <option value="">All branches</option>
          @foreach(($branches ?? []) as $b)
            <option value="{{ $b['id'] }}" {{ ($currentBranchId ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
          @endforeach
        </select>
      </label>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <button id="refreshBtn" onclick="refreshAll()">Refresh</button>
      <form method="GET" action="{{ route('reports.export') }}" target="_blank" id="exportForm" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="period" id="exportPeriod" value="daily">
        <input type="hidden" name="branchId" id="exportBranchId" value="">
        <select name="report">
          <option value="sales">Sales</option>
          <option value="top-items">Top Items</option>
          <option value="busy-slots">Busy Slots</option>
        </select>
        <select name="type">
          <option value="csv">CSV</option>
          <option value="xlsx">Excel</option>
          <option value="pdf">PDF</option>
        </select>
        <button type="submit" id="exportBtn">Export</button>
      </form>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr;gap:16px;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
      <h3 style="margin:0 0 8px;">Sales</h3>
      <canvas id="salesChart" height="90"></canvas>
      <div style="overflow:auto;margin-top:8px;">
        <table style="width:100%;border-collapse:collapse;min-width:480px;">
          <thead>
            <tr>
              <th style="text-align:left;border-bottom:1px solid #e5e7eb;padding:6px;">Period</th>
              <th style="text-align:right;border-bottom:1px solid #e5e7eb;padding:6px;">Orders</th>
              <th style="text-align:right;border-bottom:1px solid #e5e7eb;padding:6px;">Total</th>
            </tr>
          </thead>
          <tbody id="salesTable"></tbody>
        </table>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
        <h3 style="margin:0 0 8px;">Top Items</h3>
        <canvas id="itemsChart" height="120"></canvas>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
        <h3 style="margin:0 0 8px;">Busiest Hours</h3>
        <canvas id="slotsChart" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
function btnStart(btn, text){ if(!btn) return; btn.disabled = true; btn.dataset._orig = btn.innerHTML; btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:-2px;"></span> ' + (text||'Loading...'); }
function btnDone(btn){ if(!btn) return; btn.disabled = false; if(btn.dataset._orig){ btn.innerHTML = btn.dataset._orig; delete btn.dataset._orig; } }
let salesChart, itemsChart, slotsChart;
function syncExportInputs(){
  document.getElementById('exportPeriod').value = document.getElementById('period').value;
  document.getElementById('exportBranchId').value = document.getElementById('branchId').value;
}
async function fetchJson(url, params){
  const qs = new URLSearchParams(params).toString();
  const res = await fetch(url + '?' + qs, { headers: { 'Accept': 'application/json' } });
  return await res.json();
}
function filters(){
  return { period: document.getElementById('period').value, branchId: document.getElementById('branchId').value };
}
function fmt(n){ return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n||0); }
function upsertChart(inst, ctx, type, data, opts){ if(inst){ inst.data=data; inst.options=opts||{}; inst.update(); return inst; } return new Chart(ctx,{type,data,options:opts||{}}); }
async function loadSales(){
  const data = await fetchJson("{{ route('reports.sales') }}", filters());
  const labels = data.map(d=>d.period);
  const totals = data.map(d=>d.total);
  const orders = data.map(d=>d.orders);
  const ctx = document.getElementById('salesChart');
  salesChart = upsertChart(salesChart, ctx, 'line', {labels, datasets:[{label:'Total', data:totals, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.2)', tension:.3}]});
  const tbody = document.getElementById('salesTable');
  tbody.innerHTML = data.map(d=>`<tr><td style="padding:6px;border-bottom:1px solid #f3f4f6;">${d.period}</td><td style="padding:6px;text-align:right;border-bottom:1px solid #f3f4f6;">${d.orders}</td><td style="padding:6px;text-align:right;border-bottom:1px solid #f3f4f6;">${fmt(d.total)}</td></tr>`).join('');
}
async function loadTopItems(){
  const f = filters(); f.limit = 10;
  const data = await fetchJson("{{ route('reports.top_items') }}", f);
  const labels = data.map(d=>d.name);
  const qty = data.map(d=>d.qty);
  const ctx = document.getElementById('itemsChart');
  itemsChart = upsertChart(itemsChart, ctx, 'bar', {labels, datasets:[{label:'Qty', data:qty, backgroundColor:'#6366f1'}]}, {plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}});
}
async function loadBusySlots(){
  const data = await fetchJson("{{ route('reports.busy_slots') }}", filters());
  const labels = data.map(d=>d.hour);
  const cnt = data.map(d=>d.orders);
  const ctx = document.getElementById('slotsChart');
  slotsChart = upsertChart(slotsChart, ctx, 'bar', {labels, datasets:[{label:'Orders', data:cnt, backgroundColor:'#f59e0b'}]}, {plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}});
}
async function refreshAll(){
  syncExportInputs();
  const btn = document.getElementById('refreshBtn');
  btnStart(btn, 'Refreshing...');
  try {
    await Promise.all([loadSales(), loadTopItems(), loadBusySlots()]);
  } finally { btnDone(btn); }
}
document.getElementById('period').addEventListener('change', refreshAll);
document.getElementById('branchId').addEventListener('change', refreshAll);
syncExportInputs();
refreshAll();
document.getElementById('exportForm').addEventListener('submit', function(){ btnStart(document.getElementById('exportBtn'), 'Exporting...'); setTimeout(()=>btnDone(document.getElementById('exportBtn')), 1500); });
</script>
@endsection
