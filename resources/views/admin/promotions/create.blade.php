@extends('layouts.admin')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
  <div>
    <h1 class="page-title mb-0">Add Promotion</h1>
    <div class="page-subtitle">Create a new promotion for a branch.</div>
  </div>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-body">
    <form action="{{ route('promotions.store') }}" method="post" enctype="multipart/form-data">
      @csrf

      @if(session('role') === 'admin')
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="restaurantId" class="form-label">Restaurant</label>
            <select id="restaurantId" name="restaurantId" class="form-select" onchange="onRestaurantChange(this)">
              @foreach(($restaurants ?? []) as $r)
                <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '') === $r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label for="branchId" class="form-label">Branch</label>
            <select id="branchId" name="branchId" class="form-select" required>
              @foreach(($branches ?? []) as $b)
                <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
              @endforeach
            </select>
          </div>
        </div>
      @elseif(session('role') === 'restaurant_admin')
        <div class="mb-3">
          <label for="branchId" class="form-label">Branch</label>
          <select id="branchId" name="branchId" class="form-select" required>
            @foreach(($branches ?? []) as $b)
              <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="row g-3">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="name_en" class="form-label">Promotion Name (English)</label>
            <input type="text" id="name_en" name="name_en" class="form-control" value="{{ old('name_en') }}" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="name_fi" class="form-label">Promotion Name (Finnish)</label>
            <input type="text" id="name_fi" name="name_fi" class="form-control" value="{{ old('name_fi') }}" required>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select" required>
              <option value="active" {{ old('status')==='active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ old('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="description_en" class="form-label">Description (English)</label>
            <textarea id="description_en" name="description_en" class="form-control" rows="3">{{ old('description_en') }}</textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="description_fi" class="form-label">Description (Finnish)</label>
            <textarea id="description_fi" name="description_fi" class="form-control" rows="3">{{ old('description_fi') }}</textarea>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label for="image" class="form-label">Image</label>
          <input type="file" id="image" name="image" class="form-control" accept="image/*">
          <small class="text-muted">Recommended resolution: 1200x600px (or similar 2:1). Max 5MB.</small>
        </div>
        <div class="col-md-3">
          <label for="discountType" class="form-label">Discount Type</label>
          <select id="discountType" name="discountType" class="form-select" required>
            <option value="percent" {{ old('discountType')==='percent' ? 'selected' : '' }}>Percent</option>
            <option value="fixed" {{ old('discountType')==='fixed' ? 'selected' : '' }}>Fixed Amount</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="discountValue" class="form-label">Discount Value</label>
          <input type="number" step="0.01" min="0" id="discountValue" name="discountValue" class="form-control" value="{{ old('discountValue', 0) }}" required>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label for="startsAt" class="form-label">Starts At</label>
          <input type="date" id="startsAt" name="startsAt" class="form-control" value="{{ old('startsAt') }}">
        </div>
        <div class="col-md-6">
          <label for="endsAt" class="form-label">Ends At</label>
          <input type="date" id="endsAt" name="endsAt" class="form-control" value="{{ old('endsAt') }}">
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Create Promotion</button>
        <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

@if(session('role') === 'admin')
<script>
function onRestaurantChange(sel){
  const rid = sel.value;
  const url = new URL(window.location.href);
  url.searchParams.set('restaurantId', rid);
  window.location.href = url.toString();
}
</script>
@endif
@endsection
