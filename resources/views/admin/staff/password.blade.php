@extends('layouts.admin')
@section('content')
<h2>Reset Password</h2>
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
<div class="card">
  <div class="card-body">
    <div class="mb-3">
      <div class="text-muted small">Staff</div>
      <div class="fw-semibold">{{ $staffName }}</div>
      <div class="text-muted">{{ $staffEmail }}</div>
    </div>
    <form method="POST" action="{{ route('staff.password.update', $staffId) }}" class="mt-2">
      @csrf
      @method('PUT')
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required minlength="6">
      </div>
      <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-primary" type="submit">Save</button>
    </form>
  </div>
</div>
@endsection
