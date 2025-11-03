<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .sidebar { min-height: 100vh; background: #222; color: #fff; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 0.75rem 1rem; }
        .sidebar a.active, .sidebar a:hover { background: #444; }
        #global-loading-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.35); display: none; align-items: center; justify-content: center; z-index: 1050; }
        #global-loading-box { background: #111827; color:#fff; padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .spinner { width: 18px; height: 18px; border: 3px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div id="global-loading-overlay">
  <div id="global-loading-box"><div class="spinner"></div><div>Loading...</div></div>
  </div>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar py-4">
            <h4 class="text-center mb-4">Admin</h4>
            <a href="{{ url('/admin') }}" class="{{ request()->is('admin') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ url('/admin/orders') }}" class="{{ request()->is('admin/orders') ? 'active' : '' }}">Orders</a>
            <a href="{{ url('/admin/menu') }}" class="{{ request()->is('admin/menu') ? 'active' : '' }}">Menu</a>
            <a href="{{ url('/admin/staff') }}" class="{{ request()->is('admin/staff') ? 'active' : '' }}">Staff</a>
            <a href="{{ url('/admin/reports') }}" class="{{ request()->is('admin/reports') ? 'active' : '' }}">Reports</a>
            <a href="{{ url('/admin/notifications') }}" class="{{ request()->is('admin/notifications') ? 'active' : '' }}">Notifications</a>
            <a href="{{ url('/admin/settings') }}" class="{{ request()->is('admin/settings') ? 'active' : '' }}">Settings</a>
            <hr>
            <a href="{{ url('/logout') }}">Logout</a>
        </nav>
        <main class="col-md-10 py-4">
            <div class="d-flex justify-content-end align-items-center mb-3">
                @if(session('role') !== 'branch_admin')
                @isset($branches)
                <form method="POST" action="{{ route('branch.select') }}" class="d-flex align-items-center gap-2">
                    @csrf
                    <select name="branchId" class="form-select form-select-sm" style="width:260px" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                          <option value="{{ $b['id'] }}" {{ ($currentBranchId ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                </form>
                @if(!empty($currentBranchId))
                <form method="POST" action="{{ route('branch.clear') }}" class="ms-2">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary">Clear</button>
                </form>
                @endif
                @endisset
                @endif
            </div>
            @yield('content')
        </main>
    </div>
</div>
<script>
(function(){
  const overlay = document.getElementById('global-loading-overlay');
  let pending = 0; let timer;
  function show(){ clearTimeout(timer); overlay.style.display = 'flex'; }
  function hide(){ timer = setTimeout(()=>{ if(pending===0) overlay.style.display = 'none'; }, 120); }
  function start(){ pending++; if(pending===1) show(); }
  function done(){ pending = Math.max(0, pending-1); if(pending===0) hide(); }

  const origFetch = window.fetch;
  window.fetch = function(){ start(); return origFetch.apply(this, arguments).finally(done); };

  document.addEventListener('submit', function(e){ const form = e.target; if(form && !form.hasAttribute('data-no-loading')) { show(); } }, true);
  window.addEventListener('beforeunload', function(){ show(); });

  const navLinks = document.querySelectorAll('a[href]');
  navLinks.forEach(a=>{
    a.addEventListener('click', function(ev){
      const url = a.getAttribute('href') || '';
      if(url.startsWith('#') || a.target === '_blank') return;
      show();
    });
  });
})();
</script>
</body>
</html>
