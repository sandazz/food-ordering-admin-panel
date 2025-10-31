@extends('layouts.admin')
@section('content')
<h2>Create Restaurant</h2>
<form method="POST" action="{{ route('settings.restaurants.store') }}" class="mt-3">
  @csrf
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3"></textarea>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Logo URL</label>
      <input type="url" name="logoUrl" class="form-control">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Tax Rate</label>
      <input type="number" step="0.01" name="taxRate" class="form-control" value="0">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Service Charge</label>
      <input type="number" step="0.01" name="serviceCharge" class="form-control" value="0">
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>
  <a href="{{ route('settings.restaurants') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
