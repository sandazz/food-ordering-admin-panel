@extends('layouts.admin')
@section('content')
<h2>Restaurant Context</h2>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(session('role') === 'admin')
  <form method="GET" action="{{ route('settings.context') }}" class="row g-3 align-items-end mb-4">
    <div class="col-md-6">
      <label class="form-label">Restaurant</label>
      <select name="restaurantId" class="form-select" onchange="this.form.submit()">
        <option value="">-- Choose Restaurant --</option>
        @foreach($restaurants as $r)
          <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '') === $r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6 text-end">
      <a href="{{ route('settings.restaurants') }}" class="btn btn-outline-primary">Manage Restaurants</a>
    </div>
  </form>
@else
  <div class="alert alert-info mb-4">
    Current Restaurant: <strong>{{ collect($restaurants)->firstWhere('id', $selectedRestaurantId)['name'] ?? $selectedRestaurantId }}</strong>
  </div>
@endif
@if(!empty($selectedRestaurantId))
  @if(session('role') === 'admin')
    <form method="POST" action="{{ route('settings.context.save') }}" class="row g-3">
      @csrf
      <input type="hidden" name="restaurantId" value="{{ $selectedRestaurantId }}">
      <div class="col-md-12 d-flex align-items-end justify-content-between">
        <div>
          <div class="text-muted">Current Restaurant: <strong>{{ collect($restaurants)->firstWhere('id', $selectedRestaurantId)['name'] ?? $selectedRestaurantId }}</strong></div>
          <div class="text-muted small">Branch is selected per-page from top right selector.</div>
        </div>
        <a href="{{ route('settings.branches', $selectedRestaurantId) }}" class="btn btn-outline-primary">Manage Branches</a>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Save Restaurant</button>
      </div>
    </form>
  @else
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <div class="text-muted">Current Restaurant: <strong>{{ collect($restaurants)->firstWhere('id', $selectedRestaurantId)['name'] ?? $selectedRestaurantId }}</strong></div>
        <div class="text-muted small">Branch is selected per-page from top right selector.</div>
      </div>
      <a href="{{ route('settings.branches', $selectedRestaurantId) }}" class="btn btn-outline-primary">Manage Branches</a>
    </div>
  @endif
@endif
@endsection
