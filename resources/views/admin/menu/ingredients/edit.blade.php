@extends('layouts.admin')
@section('content')
<h2>Edit Ingredient</h2>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('menu.ingredients.update', $ingredient['id']) }}" class="mt-3">
  @csrf
  @method('PUT')
  @if(request('branchId'))
    <input type="hidden" name="branchId" value="{{ request('branchId') }}">
  @endif
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (EN)</label>
      <input type="text" name="name_en" class="form-control" value="{{ $ingredient['name_en'] }}" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Name (FI)</label>
      <input type="text" name="name_fi" class="form-control" value="{{ $ingredient['name_fi'] }}" required>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (EN)</label>
    <textarea name="description_en" class="form-control" rows="3">{{ $ingredient['description_en'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Description (FI)</label>
    <textarea name="description_fi" class="form-control" rows="3">{{ $ingredient['description_fi'] }}</textarea>
  </div>
  <a href="{{ route('menu.ingredients.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
