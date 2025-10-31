@extends('layouts.app')

@section('content')
<div class="container" style="max-width:420px;margin-top:4rem;">
    <h3 class="mb-3">Login</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" class="form-control" name="password" required>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Login with Firebase</button>
        </div>
    </form>
</div>
@endsection
