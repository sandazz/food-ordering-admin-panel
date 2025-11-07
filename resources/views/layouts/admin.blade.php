<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Food Admin - Modern Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --dark-bg: #1e293b;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .sidebar { 
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0c1420 100%);
            color: #fff; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            border-right: 1px solid #334155;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar .logo {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #334155;
            /* text-align: start; */
            text-align: center;
        }
        
        .sidebar .logo .logo-image {
            max-width: 120px;
            max-height: 60px;
            width: auto;
            height: auto;
            margin-bottom: 0.75rem;
            border-radius: 0.3rem;
        }
        
        .sidebar .logo h4 {
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .sidebar .nav-section {
            padding: 1rem 0;
        }
        
        .sidebar .nav-section-title {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar a { 
            color: #cbd5e1; 
            text-decoration: none; 
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem; 
            margin: 0.125rem 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
            gap: 0.75rem;
        }
        
        .sidebar a i {
            width: 1.25rem;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar a.active { 
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }
        
        .sidebar a:hover:not(.active) { 
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(2px);
        }
        
        .main-content {
            background: transparent;
            min-height: 100vh;
            margin-left: 280px;
            overflow-y: auto;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .content-wrapper {
            padding: 0 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }
        
        .card:hover {
            box-shadow: var(--card-shadow-lg);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .stats-card {
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .stats-card .icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stats-card.primary .icon { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); }
        .stats-card.success .icon { background: linear-gradient(135deg, var(--success-color), #059669); }
        .stats-card.warning .icon { background: linear-gradient(135deg, var(--warning-color), #d97706); }
        .stats-card.info .icon { background: linear-gradient(135deg, var(--info-color), #2563eb); }
        
        .table {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .table thead th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: none;
            font-weight: 600;
            color: #374151;
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table tbody td {
            border: none;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .badge {
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        #global-loading-overlay { 
            position: fixed; 
            inset: 0; 
            background: rgba(15, 23, 42, 0.8); 
            display: none; 
            align-items: center; 
            justify-content: center; 
            z-index: 1050;
            backdrop-filter: blur(4px);
        }
        
        #global-loading-box { 
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color:#fff; 
            padding: 1.5rem 2rem; 
            border-radius: 1rem; 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .spinner { 
            width: 20px; 
            height: 20px; 
            border: 3px solid rgba(255,255,255,0.3); 
            border-top-color: var(--primary-color); 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
        }
        
        @keyframes spin { 
            to { transform: rotate(360deg); } 
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-preparing { background: #dbeafe; color: #1e40af; }
        .status-ready { background: #e0e7ff; color: #3730a3; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        @media (max-width: 1024px) {
            .content-wrapper {
                padding: 0 1rem;
            }
            
            .top-bar .d-flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .btn-group {
                order: 1;
                width: 100%;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 768px) {
            body {
                font-size: 13px;
            }
            
            .sidebar {
                left: -280px;
                transition: left 0.3s ease;
                width: 280px;
                z-index: 1050;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .content-wrapper {
                padding: 0 0.75rem;
                padding-bottom: 1rem;
            }
            
            .top-bar {
                padding: 0.75rem 0;
                margin-bottom: 1rem;
            }
            
            .top-bar .content-wrapper {
                padding: 0 0.75rem;
            }
            
            .top-bar .d-flex {
                flex-direction: row;
                align-items: stretch;
                gap: 0.75rem;
            }
            
            .top-bar .d-flex > div:first-child {
                order: 1;
            }
            
            .top-bar .d-flex > div:last-child {
                order: 2;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }
            
            .page-subtitle {
                font-size: 0.875rem;
                margin-bottom: 1rem;
            }
            
            .card {
                border-radius: 0.5rem;
                margin-bottom: 1rem;
            }
            
            .card-header {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .stats-card {
                margin-bottom: 0.75rem;
            }
            
            .stats-card .icon {
                width: 2.5rem;
                height: 2.5rem;
                font-size: 1.25rem;
            }
            
            .table-responsive {
                border-radius: 0.5rem;
                box-shadow: var(--card-shadow);
            }
            
            .table thead th {
                padding: 0.75rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .table tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
            }
            
            .btn-group {
                flex-shrink: 0;
            }
            
            .btn-group .btn {
                min-width: 32px;
                padding: 0.4rem 0.6rem;
            }
            
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
            
            .sidebar a {
                padding: 0.625rem 1rem;
                font-size: 0.9rem;
            }
            
            .sidebar .nav-section-title {
                font-size: 0.7rem;
                padding: 0 1rem;
            }
            
            /* Mobile backdrop overlay */
            .mobile-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .mobile-backdrop.show {
                opacity: 1;
                visibility: visible;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }
            
            .content-wrapper {
                padding: 0 0.5rem;
                padding-bottom: 1rem;
            }
            
            .top-bar .content-wrapper {
                padding: 0 0.5rem;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .card-header {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .stats-card .icon {
                width: 2rem;
                height: 2rem;
                font-size: 1rem;
            }
            
            .table thead th {
                padding: 0.5rem 0.25rem;
                font-size: 0.7rem;
            }
            
            .table tbody td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }
            
            .btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
            
            /* Keep language selector compact and horizontal */
            .btn-group {
                flex-shrink: 0;
            }
            
            .btn-group .btn {
                min-width: 32px;
                flex: 0 0 auto;
            }
            
            .top-bar .d-flex > div:last-child {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: center;
            }
            
            .form-select {
                width: 100% !important;
                flex: 1 1 auto;
                min-width: 120px;
            }
            
            /* Keep language selector horizontal */
            .btn-group {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            
            /* Hide less important elements on very small screens */
            .page-subtitle {
                display: none;
            }
            
            .top-bar small {
                display: none;
            }
        }

        @media (max-width: 360px) {
            .sidebar a {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .content-wrapper {
                padding: 0 0.25rem;
                padding-bottom: 1rem;
            }
            
            .top-bar .content-wrapper {
                padding: 0 0.25rem;
            }
        }
    </style>
</head>
<body>
<div id="global-loading-overlay">
    <div id="global-loading-box">
        <div class="spinner"></div>
        <div>Loading...</div>
    </div>
</div>

<div class="container-fluid p-0">
    <!-- Mobile backdrop -->
    <div class="mobile-backdrop" id="mobileBackdrop"></div>
    
    <!-- Modern Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo">
            <img src="{{ asset('images/logo.jpeg') }}" alt="Food Admin Logo" class="logo-image">
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Main Menu</div>
            <a href="{{ url('/admin') }}" class="{{ request()->is('admin') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                {{ \App\Utils\UIStrings::t('nav.dashboard') }}
            </a>
            <a href="{{ url('/admin/orders') }}" class="{{ request()->is('admin/orders') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                {{ \App\Utils\UIStrings::t('nav.orders') }}
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Menu Management</div>
            <a href="{{ url('/admin/menu') }}" class="{{ request()->is('admin/menu') ? 'active' : '' }}">
                <i class="bi bi-card-list"></i>
                {{ \App\Utils\UIStrings::t('nav.menu') }}
            </a>
            <a href="{{ route('menu.sizes.index') }}" class="{{ request()->is('admin/menu/sizes*') ? 'active' : '' }}">
                <i class="bi bi-rulers"></i>
                {{ \App\Utils\UIStrings::t('nav.menu_sizes') }}
            </a>
            <a href="{{ route('menu.bases.index') }}" class="{{ request()->is('admin/menu/bases*') ? 'active' : '' }}">
                <i class="bi bi-layers"></i>
                {{ \App\Utils\UIStrings::t('nav.menu_bases') }}
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <a href="{{ url('/admin/staff') }}" class="{{ request()->is('admin/staff') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                {{ \App\Utils\UIStrings::t('nav.staff') }}
            </a>
            <a href="{{ url('/admin/reports') }}" class="{{ request()->is('admin/reports') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i>
                {{ \App\Utils\UIStrings::t('nav.reports') }}
            </a>
            @if(session('role') !== 'branch_admin')
            <a href="{{ url('/admin/notifications') }}" class="{{ request()->is('admin/notifications') ? 'active' : '' }}">
                <i class="bi bi-bell"></i>
                {{ \App\Utils\UIStrings::t('nav.notifications') }}
            </a>      
            <a href="{{ url('/admin/settings') }}" class="{{ request()->is('admin/settings') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                {{ \App\Utils\UIStrings::t('nav.settings') }}
            </a>
            @endif
        </div>
        
        <div class="nav-section" style="margin-top: auto;">
            <a href="{{ url('/logout') }}" style="margin-top: 2rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-box-arrow-right"></i>
                {{ \App\Utils\UIStrings::t('nav.logout') }}
            </a>
        </div>
    </nav>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="content-wrapper">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="btn d-lg-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        <div>
                            <h6 class="mb-0 text-muted">Welcome back!</h6>
                            <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        @if(session('role') !== 'branch_admin')
                        @isset($branches)
                        <form method="POST" action="{{ route('branch.select') }}" class="d-flex align-items-center gap-2 flex-grow-1">
                            @csrf
                            <select name="branchId" class="form-select form-select-sm" style="min-width:180px;" onchange="this.form.submit()">
                                <option value="">{{ \App\Utils\UIStrings::t('branches.all') }}</option>
                                @foreach($branches as $b)
                                  <option value="{{ $b['id'] }}" {{ ($currentBranchId ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                                @endforeach
                            </select>
                        </form>
                        @if(!empty($currentBranchId))
                        <form method="POST" action="{{ route('branch.clear') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>
                                <span class="d-none d-md-inline">{{ \App\Utils\UIStrings::t('branches.clear') }}</span>
                                <span class="d-md-none">Clear</span>
                            </button>
                        </form>
                        @endif
                        @endisset
                        @endif
                        
                        <form method="POST" action="{{ route('ui.lang.set') }}" class="d-flex align-items-center">
                            @csrf
                            @php($uiLang = session('ui_lang','en'))
                            <input type="hidden" name="lang" id="uiLangInput" value="{{ $uiLang }}">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="submit" class="btn {{ $uiLang==='en' ? 'btn-primary' : 'btn-outline-primary' }}" onclick="document.getElementById('uiLangInput').value='en'">EN</button>
                                <button type="submit" class="btn {{ $uiLang==='fi' ? 'btn-primary' : 'btn-outline-primary' }}" onclick="document.getElementById('uiLangInput').value='fi'">FI</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global loading overlay functionality
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

// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mobileBackdrop = document.getElementById('mobileBackdrop');
    
    function openSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('show');
            mobileBackdrop.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
    }
    
    function closeSidebar() {
        sidebar.classList.remove('show');
        mobileBackdrop.classList.remove('show');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    
    // Close sidebar when clicking backdrop
    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) && 
            !sidebarToggle.contains(e.target)) {
            closeSidebar();
        }
    });
    
    // Close sidebar on window resize if mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // Handle navigation clicks on mobile
    const navLinks = sidebar.querySelectorAll('a[href]');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Add touch gestures for swipe to close
    let touchStartX = 0;
    let touchEndX = 0;
    
    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        const swipeDistance = touchStartX - touchEndX;
        
        // If swiped left more than 50px, close sidebar
        if (swipeDistance > 50 && window.innerWidth <= 768) {
            closeSidebar();
        }
    });
});

// Add smooth animations and transitions
document.addEventListener('DOMContentLoaded', function() {
    // Add stagger animation to cards
    const cards = document.querySelectorAll('.card, .stats-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.animation = 'fadeInUp 0.6s ease forwards';
    });
    
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
