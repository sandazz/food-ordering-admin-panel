@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('items.edit.title') }}</h2>
<form method="POST" action="{{ route('menu.items.update', [$item['categoryId'], $item['id']]) }}" class="mt-3" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" value="{{ $item['name_en'] ?? $item['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" value="{{ $item['name_fi'] ?? '' }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.description_en') }}</label>
    <textarea name="description_en" class="form-control" rows="3">{{ $item['description_en'] ?? $item['description'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.description_fi') }}</label>
    <textarea name="description_fi" class="form-control" rows="3">{{ $item['description_fi'] ?? '' }}</textarea>
  </div>
  @php($selSizeMap = collect($item['sizesOptions'] ?? [])->keyBy('id'))
  @if(!empty($sizes))
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.sizes_select') }}</label>
    <div class="list-group">
      @foreach($sizes as $s)
        @php($checked = $selSizeMap->has($s['id']))
        <label class="list-group-item d-flex align-items-center" style="gap:.5rem;">
          <input type="checkbox" name="sizes[{{ $s['id'] }}]" value="1" onchange="toggleInline(this)" {{ $checked ? 'checked' : '' }}>
          <span style="min-width:160px;">{{ $s['name'] }} (€{{ number_format($s['price'],2) }})</span>
          <input type="number" step="0.01" min="0" name="sizes_price[{{ $s['id'] }}]" class="form-control form-control-sm" style="max-width:160px; {{ $checked ? '' : 'display:none;' }}" placeholder="{{ \App\Utils\UIStrings::t('placeholder.price_override_optional') }}" value="{{ $checked ? $selSizeMap[$s['id']]['price'] : '' }}">
        </label>
      @endforeach
    </div>
  </div>
  @endif
  @php($selBaseMap = collect($item['basesOptions'] ?? [])->keyBy('id'))
  @if(!empty($bases))
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.bases_select') }}</label>
    <div class="list-group">
      @foreach($bases as $b)
        @php($checked = $selBaseMap->has($b['id']))
        <label class="list-group-item d-flex align-items-center" style="gap:.5rem;">
          <input type="checkbox" name="bases[{{ $b['id'] }}]" value="1" onchange="toggleInline(this)" {{ $checked ? 'checked' : '' }}>
          <span style="min-width:160px;">{{ $b['name'] }} (€{{ number_format($b['price'],2) }})</span>
          <input type="number" step="0.01" min="0" name="bases_price[{{ $b['id'] }}]" class="form-control form-control-sm" style="max-width:160px; {{ $checked ? '' : 'display:none;' }}" placeholder="{{ \App\Utils\UIStrings::t('placeholder.price_override_optional') }}" value="{{ $checked ? $selBaseMap[$b['id']]['price'] : '' }}">
        </label>
      @endforeach
    </div>
  </div>
  @endif
  @if(!empty($isSpecialCategory) && $isSpecialCategory && !empty($ingredients))
  @php($selIngMap = collect($item['ingredientsOptions'] ?? [])->keyBy('id'))
  <div class="mb-3">
    <label class="form-label">Ingredients</label>
    <div class="list-group">
      @foreach($ingredients as $ing)
        @php($checked = $selIngMap->has($ing['id']))
        <div class="list-group-item">
          <label class="d-flex align-items-center" style="gap:.5rem;">
            <input type="checkbox" name="ingredients[{{ $ing['id'] }}]" value="1" {{ $checked ? 'checked' : '' }} onchange="toggleIngredient(this)">
            <span style="min-width:160px;">{{ $ing['name'] }}</span>
          </label>
          @if(!empty($sizes))
          <div class="mt-2 ing-size-grid" data-ingredient="{{ $ing['id'] }}" style="{{ $checked ? '' : 'display:none;' }}">
            @foreach($sizes as $s)
              <div class="d-flex align-items-center mb-1 ing-size-row" data-size="{{ $s['id'] }}" style="gap:.5rem; display:none;">
                <span class="text-muted" style="min-width:120px;">{{ $s['name'] }}</span>
                <input type="number" min="0" step="1" name="ingredients_max[{{ $ing['id'] }}][{{ $s['id'] }}]" class="form-control form-control-sm" style="max-width:140px;" placeholder="Max (0=all)" value="{{ $checked ? (data_get($selIngMap[$ing['id']], 'sizeMax.'.$s['id']) ?? '') : '' }}">
              </div>
            @endforeach
          </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.item_price_optional') }}</label>
    <input type="number" step="0.01" name="price" class="form-control" value="{{ $item['price'] }}" placeholder="{{ \App\Utils\UIStrings::t('placeholder.item_price_hint') }}">
  </div>
  <div class="mb-3">
    <label class="form-label">Offer Price (optional)</label>
    <input type="number" step="0.01" name="offerPrice" class="form-control" value="{{ $item['offerPrice'] ?? '' }}" placeholder="Enter promotional/discounted price">
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="availability" value="1" id="availability" {{ $item['availability'] ? 'checked' : '' }}>
    <label class="form-check-label" for="availability">{{ \App\Utils\UIStrings::t('field.available') }}</label>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.image_url') }}</label>
    <input type="url" name="imageUrl" class="form-control" value="{{ $item['imageUrl'] }}">
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Upload Image</label>
      <input type="file" name="image" id="itemImageInput" class="form-control" accept="image/*">
      <div class="mt-2">
        <img id="itemImagePreview" src="{{ $item['imageUrl'] ?? '' }}" alt="Item Image" style="max-height:80px;{{ empty($item['imageUrl']) ? 'display:none;' : '' }}"/>
      </div>
    </div>
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">{{ \App\Utils\UIStrings::t('common.cancel') }}</a>
  <button class="btn btn-primary">{{ \App\Utils\UIStrings::t('common.update') }}</button>
</form>
<script>
  (function(){
    const inp = document.getElementById('itemImageInput');
    const img = document.getElementById('itemImagePreview');
    function bind(){ if(!(inp&&img)) return; inp.addEventListener('change', function(){ const f=this.files&&this.files[0]; if(f){ img.src=URL.createObjectURL(f); img.style.display=''; } }); }
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', bind); } else { bind(); }
  })();
</script>
<script>
function toggleInline(cb){
  var input = cb.parentElement.querySelector('input[type=number]');
  if(input){ input.style.display = cb.checked ? '' : 'none'; }
}
function isSizeSelected(sizeId){
  var checks = document.querySelectorAll('input[name^="sizes["]');
  for(var i=0;i<checks.length;i++){
    var nm = checks[i].name;
    var sid = nm.substring(nm.indexOf('[')+1, nm.lastIndexOf(']'));
    if(sid === String(sizeId)){
      return !!checks[i].checked;
    }
  }
  return false;
}
function toggleIngredient(cb){
  var wrap = cb.closest('.list-group-item').querySelector('.ing-size-grid');
  if(wrap){
    if(cb.checked){
      wrap.style.display = '';
      wrap.querySelectorAll('.ing-size-row').forEach(function(row){
        var sz = row.getAttribute('data-size');
        row.style.display = isSizeSelected(sz) ? '' : 'none';
      });
      // hide entire grid if no rows visible
      var anyShown = Array.prototype.some.call(wrap.querySelectorAll('.ing-size-row'), function(row){ return row.style.display !== 'none'; });
      wrap.style.display = anyShown ? '' : 'none';
    } else {
      wrap.style.display = 'none';
    }
  }
  syncSizeVisibility();
}
function syncSizeVisibility(){
  document.querySelectorAll('.ing-size-grid').forEach(function(grid){
    var anyShown = false;
    grid.querySelectorAll('.ing-size-row').forEach(function(row){
      var sz = row.getAttribute('data-size');
      var show = isSizeSelected(sz);
      row.style.display = show ? '' : 'none';
      if(show){ anyShown = true; }
    });
    var cb = grid.closest('.list-group-item').querySelector('input[type="checkbox"][name^="ingredients["]');
    if(cb && cb.checked && anyShown){
      grid.style.display = '';
    } else {
      grid.style.display = 'none';
    }
  });
}
document.addEventListener('change', function(e){
  if(e.target && e.target.name && e.target.name.startsWith('sizes[')){
    // if a size is unchecked, clear its values across all ingredients
    var m = e.target.name.match(/sizes\[(.+)\]/);
    if(m){
      var sizeId = m[1];
      if(!e.target.checked){
        document.querySelectorAll('input[name^="ingredients_max["]').forEach(function(inp){
          var re = new RegExp('ingredients_max\\\[[^\\]]+\\\]\\\['+sizeId+'\\\]');
          if(re.test(inp.name)){ inp.value = ''; }
        });
      }
    }
    syncSizeVisibility();
  }
});
document.addEventListener('DOMContentLoaded', function(){ syncSizeVisibility(); });
</script>
@endsection
