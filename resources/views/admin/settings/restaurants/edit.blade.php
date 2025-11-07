@extends('layouts.admin')
@section('content')
<h2>Edit Restaurant</h2>
<form method="POST" action="{{ route('settings.restaurants.update', $restaurant['id']) }}" class="mt-3" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ $restaurant['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ $restaurant['description'] }}</textarea>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Logo URL</label>
      <input type="url" name="logoUrl" class="form-control" value="{{ $restaurant['logoUrl'] }}" placeholder="https://... (optional)">
      <div class="form-text">Alternatively, upload a file below.</div>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Upload Logo</label>
      <input type="file" name="logo" id="logoInput" class="form-control" accept="image/*">
      <div class="mt-2">
        <img id="logoPreview" src="{{ $restaurant['logoUrl'] ?? '' }}" alt="Logo" style="max-height:80px;{{ empty($restaurant['logoUrl']) ? 'display:none;' : '' }}"/>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Tax Rate</label>
      <input type="number" step="0.01" name="taxRate" class="form-control" value="{{ $restaurant['taxRate'] }}">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Service Charge</label>
      <input type="number" step="0.01" name="serviceCharge" class="form-control" value="{{ $restaurant['serviceCharge'] }}">
    </div>
  </div>
  <script>
    (function(){
      const inp = document.getElementById('logoInput');
      const img = document.getElementById('logoPreview');
      function bind(){
        if(!(inp && img)) return;
        inp.addEventListener('change', function(){
          const f = this.files && this.files[0];
          if(f){ img.src = URL.createObjectURL(f); img.style.display=''; }
        });
      }
      if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', bind);
      } else { bind(); }
    })();
  </script>
  <div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="active" {{ $restaurant['status']==='active'?'selected':'' }}>Active</option>
      <option value="inactive" {{ $restaurant['status']==='inactive'?'selected':'' }}>Inactive</option>
    </select>
  </div>
  <a href="{{ route('settings.restaurants') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Update</button>
</form>
@endsection
