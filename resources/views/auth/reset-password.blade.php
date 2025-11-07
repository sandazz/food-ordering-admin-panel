@extends('layouts.app')

@section('content')
<div class="container" style="max-width:420px;margin-top:4rem;">
    <h3 class="mb-3">Reset Password</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="oobCode" value="{{ $oobCode }}">

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input id="password" type="password" class="form-control" name="password" required>
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </div>
    </form>

    <div class="mt-3">
        <a href="{{ route('login') }}">Back to login</a>
    </div>
</div>
@endsection
