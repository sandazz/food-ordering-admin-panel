@extends('layouts.admin')
@section('content')
<h2>{{ \App\\Utils\\UIStrings::t('items.edit.title') }}</h2>
<form method="POST" action="{{ route('menu.items.update', [$item['categoryId'], $item['id']]) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" value="{{ $item['name_en'] ?? $item['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" value="{{ $item['name_fi'] ?? '' }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.description_en') }}</label>
    <textarea name="description_en" class="form-control" rows="3">{{ $item['description_en'] ?? $item['description'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.description_fi') }}</label>
    <textarea name="description_fi" class="form-control" rows="3">{{ $item['description_fi'] ?? '' }}</textarea>
  </div>
  @php($selSizeMap = collect($item['sizesOptions'] ?? [])->keyBy('id'))
  @if(!empty($sizes))
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.sizes_select') }}</label>
    <div class="list-group">
      @foreach($sizes as $s)
        @php($checked = $selSizeMap->has($s['id']))
        <label class="list-group-item d-flex align-items-center" style="gap:.5rem;">
          <input type="checkbox" name="sizes[{{ $s['id'] }}]" value="1" onchange="toggleInline(this)" {{ $checked ? 'checked' : '' }}>
          <span style="min-width:160px;">{{ $s['name'] }} ({{ number_format($s['price'],2) }})</span>
          <input type="number" step="0.01" min="0" name="sizes_price[{{ $s['id'] }}]" class="form-control form-control-sm" style="max-width:160px; {{ $checked ? '' : 'display:none;' }}" placeholder="{{ \App\\Utils\\UIStrings::t('placeholder.price_override_optional') }}" value="{{ $checked ? $selSizeMap[$s['id']]['price'] : '' }}">
        </label>
      @endforeach
    </div>
  </div>
  @endif
  @php($selBaseMap = collect($item['basesOptions'] ?? [])->keyBy('id'))
  @if(!empty($bases))
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.bases_select') }}</label>
    <div class="list-group">
      @foreach($bases as $b)
        @php($checked = $selBaseMap->has($b['id']))
        <label class="list-group-item d-flex align-items-center" style="gap:.5rem;">
          <input type="checkbox" name="bases[{{ $b['id'] }}]" value="1" onchange="toggleInline(this)" {{ $checked ? 'checked' : '' }}>
          <span style="min-width:160px;">{{ $b['name'] }} ({{ number_format($b['price'],2) }})</span>
          <input type="number" step="0.01" min="0" name="bases_price[{{ $b['id'] }}]" class="form-control form-control-sm" style="max-width:160px; {{ $checked ? '' : 'display:none;' }}" placeholder="{{ \App\\Utils\\UIStrings::t('placeholder.price_override_optional') }}" value="{{ $checked ? $selBaseMap[$b['id']]['price'] : '' }}">
        </label>
      @endforeach
    </div>
  </div>
  @endif
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.item_price_optional') }}</label>
    <input type="number" step="0.01" name="price" class="form-control" value="{{ $item['price'] }}" placeholder="{{ \App\\Utils\\UIStrings::t('placeholder.item_price_hint') }}">
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="available" value="1" id="available" {{ $item['available'] ? 'checked' : '' }}>
    <label class="form-check-label" for="available">{{ \App\\Utils\\UIStrings::t('field.available') }}</label>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.image_url') }}</label>
    <input type="url" name="imageUrl" class="form-control" value="{{ $item['imageUrl'] }}">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">{{ \App\\Utils\\UIStrings::t('common.cancel') }}</a>
  <button class="btn btn-primary">{{ \App\\Utils\\UIStrings::t('common.update') }}</button>
</form>
<script>
function toggleInline(cb){
  var input = cb.parentElement.querySelector('input[type=number]');
  if(input){ input.style.display = cb.checked ? '' : 'none'; }
}
</script>
@endsection
