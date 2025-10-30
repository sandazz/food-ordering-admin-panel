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
    </style>
</head>
<body>
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
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
