@extends('layouts.admin')
@section('content')
<h2>Edit Item</h2>
<form method="POST" action="{{ route('menu.items.update', [$item['categoryId'], $item['id']]) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ $item['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ $item['description'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Price</label>
    <input type="number" step="0.01" name="price" class="form-control" value="{{ $item['price'] }}" required>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="available" value="1" id="available" {{ $item['available'] ? 'checked' : '' }}>
    <label class="form-check-label" for="available">Available</label>
  </div>
  <div class="mb-3">
    <label class="form-label">Image URL</label>
    <input type="url" name="imageUrl" class="form-control" value="{{ $item['imageUrl'] }}">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Update</button>
</form>
@endsection
