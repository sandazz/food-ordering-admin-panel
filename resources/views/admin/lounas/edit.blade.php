@extends('layouts.admin')
@section('content')
<h2>Edit Lounas Hour</h2>
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('lounas.update', [$restaurantId, $branchId, $item['id']]) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Day of Week</label>
      <select name="dayOfWeek" class="form-select" required>
        @foreach([0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'] as $k=>$v)
          <option value="{{ $k }}" {{ (old('dayOfWeek', $item['dayOfWeek']) == $k) ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Start Time</label>
      <input type="time" name="startTime" class="form-control" value="{{ old('startTime', $item['startTime']) }}" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">End Time</label>
      <input type="time" name="endTime" class="form-control" value="{{ old('endTime', $item['endTime']) }}" required>
    </div>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active', $item['active']) ? 'checked' : '' }}>
    <label for="active" class="form-check-label">Active</label>
  </div>
  <a href="{{ route('lounas.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary" type="submit">Update</button>
</form>
@endsection
