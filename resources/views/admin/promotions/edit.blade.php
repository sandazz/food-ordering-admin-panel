@extends('layouts.admin')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
  <div>
    <h1 class="page-title mb-0">Edit Promotion</h1>
    <div class="page-subtitle">Update promotion details.</div>
  </div>
</div>

@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
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
    <form action="{{ route('promotions.update', [$restaurantId, $branchId, $promotion['id']]) }}" method="post" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="name" class="form-label">Promotion Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $promotion['name']) }}" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select" required>
              <option value="active" {{ old('status', $promotion['status'])==='active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ old('status', $promotion['status'])==='inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $promotion['description']) }}</textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Current Image</label>
          <div>
            @if(!empty($promotion['imageUrl']))
              <img src="{{ $promotion['imageUrl'] }}" alt="" style="width:240px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
            @else
              <span class="text-muted">No image</span>
            @endif
          </div>
        </div>
        <div class="col-md-6">
          <label for="image" class="form-label">Replace Image</label>
          <input type="file" id="image" name="image" class="form-control" accept="image/*">
          <small class="text-muted">Recommended resolution: 1200x600px (or similar 2:1). Max 5MB.</small>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label for="discountType" class="form-label">Discount Type</label>
          <select id="discountType" name="discountType" class="form-select" required>
            <option value="percent" {{ old('discountType', $promotion['discount']['type'])==='percent' ? 'selected' : '' }}>Percent</option>
            <option value="fixed" {{ old('discountType', $promotion['discount']['type'])==='fixed' ? 'selected' : '' }}>Fixed Amount</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="discountValue" class="form-label">Discount Value</label>
          <input type="number" step="0.01" min="0" id="discountValue" name="discountValue" class="form-control" value="{{ old('discountValue', $promotion['discount']['value']) }}" required>
        </div>
        <div class="col-md-3">
          <label for="startsAt" class="form-label">Starts At</label>
          <input type="date" id="startsAt" name="startsAt" class="form-control" value="{{ old('startsAt', $promotion['startsAt'] ? \Carbon\Carbon::parse($promotion['startsAt'])->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-3">
          <label for="endsAt" class="form-label">Ends At</label>
          <input type="date" id="endsAt" name="endsAt" class="form-control" value="{{ old('endsAt', $promotion['endsAt'] ? \Carbon\Carbon::parse($promotion['endsAt'])->format('Y-m-d') : '') }}">
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
