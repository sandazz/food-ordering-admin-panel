@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('sizes.edit.title') }}</h2>
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<form method="POST" action="{{ route('menu.sizes.update', $size['id']) }}" class="mt-3">
  @csrf
  @method('PUT')
  @if(request('branchId'))
    <input type="hidden" name="branchId" value="{{ request('branchId') }}">
  @endif
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" value="{{ $size['name_en'] ?? $size['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" value="{{ $size['name_fi'] ?? '' }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.default_price') }}</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ $size['price'] }}" required>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="isActive" value="1" id="isActive" {{ $size['isActive'] ? 'checked' : '' }}>
    <label class="form-check-label" for="isActive">{{ \App\Utils\UIStrings::t('common.active') }}</label>
  </div>
  <button class="btn btn-primary">{{ \App\Utils\UIStrings::t('common.update') }}</button>
  <a href="{{ route('menu.sizes.index') }}" class="btn btn-outline-secondary">{{ \App\Utils\UIStrings::t('common.cancel') }}</a>
</form>
@endsection
