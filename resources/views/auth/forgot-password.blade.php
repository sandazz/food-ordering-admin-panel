@extends('layouts.app')

@section('content')
<div class="container" style="max-width:420px;margin-top:4rem;">
    <h3 class="mb-3">Forgot Password</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Send reset link</button>
        </div>
    </form>

    <div class="mt-3">
        <a href="{{ route('login') }}">Back to login</a>
    </div>
</div>
@endsection
