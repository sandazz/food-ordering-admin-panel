@extends('layouts.admin')
@section('content')
<h2>Create Ingredient</h2>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('menu.ingredients.store') }}" class="mt-3">
  @csrf
  @isset($restaurants)
  <div class="mb-3">
    <label class="form-label">Restaurant</label>
    <select name="restaurantId" class="form-select" onchange="(function(sel){ if(sel.value){ window.location='{{ route('menu.ingredients.create') }}?restaurantId='+encodeURIComponent(sel.value); } })(this)" required>
      <option value="">Select restaurant</option>
      @foreach($restaurants as $r)
        <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '')===$r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
  @isset($branches)
  <div class="mb-3">
    <label class="form-label">Branch</label>
    <select name="branchId" class="form-select" required>
      @foreach($branches as $b)
        <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
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
  <a href="{{ route('menu.ingredients.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
