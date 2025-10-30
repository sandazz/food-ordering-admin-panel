@extends('layouts.admin')
@section('content')
<h2>Edit Branch</h2>
<form method="POST" action="{{ route('settings.branches.update', [$restaurantId, $branch['id']]) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ $branch['name'] }}" required>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contact</label>
      <input type="text" name="contact" class="form-control" value="{{ $branch['contact'] ?? '' }}">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="open" {{ ($branch['status'] ?? '')==='open'?'selected':'' }}>Open</option>
        <option value="closed" {{ ($branch['status'] ?? '')==='closed'?'selected':'' }}>Closed</option>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Street</label>
      <input type="text" name="street" class="form-control" value="{{ $branch['address']['street'] ?? '' }}">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">City</label>
      <input type="text" name="city" class="form-control" value="{{ $branch['address']['city'] ?? '' }}">
    </div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">State</label>
      <input type="text" name="state" class="form-control" value="{{ $branch['address']['state'] ?? '' }}">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Zip Code</label>
      <input type="text" name="zipCode" class="form-control" value="{{ $branch['address']['zipCode'] ?? '' }}">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Country</label>
      <input type="text" name="country" class="form-control" value="{{ $branch['address']['country'] ?? '' }}">
    </div>
  </div>
  <a href="{{ route('settings.branches', $restaurantId) }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Update</button>
</form>
@endsection
