@extends('layouts.admin')
@section('content')
<h2>Edit Sub-Ingredient</h2>
<form method="POST" action="{{ route('menu.ingredients.sub.update', [$sub['ingredientId'], $sub['id']]) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (EN)</label>
      <input type="text" name="name_en" class="form-control" value="{{ $sub['name_en'] }}" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (FI)</label>
      <input type="text" name="name_fi" class="form-control" value="{{ $sub['name_fi'] }}" required>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (EN)</label>
    <textarea name="description_en" class="form-control" rows="3">{{ $sub['description_en'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (FI)</label>
    <textarea name="description_fi" class="form-control" rows="3">{{ $sub['description_fi'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Price</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ number_format($sub['price'], 2, '.', '') }}" required>
  </div>
  <a href="{{ route('menu.ingredients.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
