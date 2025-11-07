@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('sizes.add') }}</h2>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('menu.sizes.store') }}" class="mt-3">
  @csrf
  @isset($restaurants)
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.restaurant') }}</label>
    <select name="restaurantId" class="form-select" onchange="(function(sel){ if(sel.value){ window.location='{{ route('menu.sizes.create') }}?restaurantId='+encodeURIComponent(sel.value); } })(this)" required>
      <option value="">{{ \App\Utils\UIStrings::t('select.restaurant') }}</option>
      @foreach($restaurants as $r)
        <option value="{{ $r['id'] }}" {{ ($selectedRestaurantId ?? '')===$r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
  @isset($branches)
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.branch') }}</label>
    <select name="branchId" class="form-select" required>
      @foreach($branches as $b)
        <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
      @endforeach
    </select>
  </div>
  @endisset
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.default_price') }}</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" required>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="isActive" value="1" id="isActive" checked>
    <label class="form-check-label" for="isActive">{{ \App\Utils\UIStrings::t('common.active') }}</label>
  </div>
  <button type="submit" class="btn btn-primary">{{ \App\Utils\UIStrings::t('common.create') }}</button>
  <a href="{{ route('menu.sizes.index') }}" class="btn btn-outline-secondary">{{ \App\Utils\UIStrings::t('common.cancel') }}</a>
</form>
@endsection
