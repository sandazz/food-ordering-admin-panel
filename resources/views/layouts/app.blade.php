<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firebase Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="/">App</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        @if(session('firebase_user'))
            <li class="nav-item"><a class="nav-link" href="#">{{ session('firebase_user.email') }}</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('/logout') }}">Logout</a></li>
        @else
            <li class="nav-item"><a class="nav-link" href="{{ url('/login') }}">Login</a></li>
        @endif
      </ul>
    </div>
  </div>
</nav>

<main class="py-4">
    @yield('content')
</main>

</body>
</html>