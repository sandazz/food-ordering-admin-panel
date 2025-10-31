@extends('layouts.admin')
@section('content')
<h2>Create Category</h2>
<form method="POST" action="{{ route('menu.categories.store') }}" class="mt-3">
  @csrf
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Display Order</label>
    <input type="number" name="displayOrder" class="form-control" value="0" min="0">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
