@extends('layouts.admin')
@section('content')
<h2>Add Sub-Ingredient</h2>
<form method="POST" action="{{ route('menu.ingredients.sub.store', $ingredientId) }}" class="mt-3">
  @csrf
  @if(request('branchId'))
    <input type="hidden" name="branchId" value="{{ request('branchId') }}">
  @endif
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (EN)</label>
      <input type="text" name="name_en" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (FI)</label>
      <input type="text" name="name_fi" class="form-control" required>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (EN)</label>
    <textarea name="description_en" class="form-control" rows="3"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (FI)</label>
    <textarea name="description_fi" class="form-control" rows="3"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Price</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" required>
  </div>
  <a href="{{ route('menu.ingredients.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
