@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('categories.create.title') }}</h2>
<form method="POST" action="{{ route('menu.categories.store') }}" class="mt-3" enctype="multipart/form-data">
  @csrf
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.description_en') }}</label>
    <textarea name="description_en" class="form-control" rows="3"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.description_fi') }}</label>
    <textarea name="description_fi" class="form-control" rows="3"></textarea>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">{{ \App\Utils\UIStrings::t('field.image_url') }}</label>
      <input type="url" name="imageUrl" class="form-control" placeholder="https://... (optional)">
      <div class="form-text">Alternatively, upload a file below.</div>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Upload Image</label>
      <input type="file" name="image" id="catImageInput" class="form-control" accept="image/*">
      <div class="mt-2">
        <img id="catImagePreview" alt="Category Image" style="max-height:80px;display:none;"/>
      </div>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.display_order') }}</label>
    <input type="number" name="displayOrder" class="form-control" value="0" min="0">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">{{ \App\Utils\UIStrings::t('common.cancel') }}</a>
  <button class="btn btn-primary">{{ \App\Utils\UIStrings::t('common.save') }}</button>
</form>
<script>
  (function(){
    const inp = document.getElementById('catImageInput');
    const img = document.getElementById('catImagePreview');
    function bind(){ if(!(inp&&img)) return; inp.addEventListener('change', function(){ const f=this.files&&this.files[0]; if(f){ img.src=URL.createObjectURL(f); img.style.display=''; } }); }
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', bind); } else { bind(); }
  })();
</script>
@endsection
