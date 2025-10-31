@extends('layouts.admin')
@section('content')
<h2>Edit Category</h2>
<form method="POST" action="{{ route('menu.categories.update', $category['id']) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ $category['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ $category['description'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Display Order</label>
    <input type="number" name="displayOrder" class="form-control" value="{{ $category['displayOrder'] }}" min="0">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Update</button>
</form>
@endsection
