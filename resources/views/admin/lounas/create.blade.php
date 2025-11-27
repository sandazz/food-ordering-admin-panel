@extends('layouts.admin')
@section('content')
<h2>Add Lounas Hour</h2>
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('lounas.store') }}" class="mt-3">
  @csrf
  @if(($role ?? null) === 'admin')
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Restaurant</label>
      <select name="restaurantId" class="form-select" id="restaurantSelect" required>
        @foreach(($restaurants ?? []) as $r)
          <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '')===$r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Branch</label>
      <select name="branchId" class="form-select" required>
        @php($sessBranch = session('branchId'))
        @foreach(($branches ?? []) as $b)
          <option value="{{ $b['id'] }}" {{ ($sessBranch && $sessBranch===$b['id']) ? 'selected' : '' }}>{{ $b['name'] }}</option>
        @endforeach
      </select>
    </div>
  </div>
  @elseif(($role ?? null) === 'restaurant_admin')
  <div class="row g-3 mb-3">
    <div class="col-md-12">
      <label class="form-label">Branch</label>
      <select name="branchId" class="form-select" required>
        @php($sessBranch = session('branchId'))
        @foreach(($branches ?? []) as $b)
          <option value="{{ $b['id'] }}" {{ ($sessBranch && $sessBranch===$b['id']) ? 'selected' : '' }}>{{ $b['name'] }}</option>
        @endforeach
      </select>
    </div>
  </div>
  @endif
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Day of Week</label>
      <select name="dayOfWeek" class="form-select" required>
        @foreach([0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'] as $k=>$v)
          <option value="{{ $k }}" {{ old('dayOfWeek')===''.$k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Start Time</label>
      <input type="time" name="startTime" class="form-control" value="{{ old('startTime','11:00') }}" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">End Time</label>
      <input type="time" name="endTime" class="form-control" value="{{ old('endTime','15:00') }}" required>
    </div>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active',1) ? 'checked' : '' }}>
    <label for="active" class="form-check-label">Active</label>
  </div>
  <a href="{{ route('lounas.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary" type="submit">Save</button>
</form>
@if(($role ?? null) === 'admin')
<script>
  (function(){
    const rs = document.getElementById('restaurantSelect');
    if(!rs) return;
    rs.addEventListener('change', function(){
      const rid = this.value;
      const url = new URL(window.location.href);
      url.searchParams.set('restaurantId', rid);
      window.location.href = url.toString();
    });
  })();
</script>
@endif
@endsection
