@extends('layouts.admin')
@section('content')
<h2>Create Staff</h2>
<form method="POST" action="{{ route('staff.store') }}" class="mt-3">
  @csrf
  @isset($restaurants)
  <div class="mb-3">
    <label class="form-label">Restaurant</label>
    <select name="restaurantId" class="form-select" onchange="(function(sel){ if(sel.value){ window.location='{{ route('staff.create') }}?restaurantId='+encodeURIComponent(sel.value); } })(this)" required>
      <option value="">Select restaurant</option>
      @foreach($restaurants as $r)
        <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '')===$r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
  @isset($branches)
  <div class="mb-3">
    <label class="form-label">Branch</label>
    <select name="branchId" class="form-select" required>
      <option value="">Select branch</option>
      @foreach($branches as $b)
        <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" minlength="6" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
      @foreach($roles as $r)
        <option value="{{ $r }}">{{ ucfirst(str_replace('_',' ',$r)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="isActive" value="1" id="isActive" checked>
    <label class="form-check-label" for="isActive">Active</label>
  </div>
  <button type="submit" class="btn btn-primary">Create</button>
  <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
@endsection
