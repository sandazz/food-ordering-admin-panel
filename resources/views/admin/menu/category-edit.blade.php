@extends('layouts.admin')
@section('content')
<h2>{{ \App\\Utils\\UIStrings::t('categories.edit.title') }}</h2>
<form method="POST" action="{{ route('menu.categories.update', $category['id']) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.name_en') }}</label>
    <input type="text" name="name_en" class="form-control" value="{{ $category['name_en'] ?? $category['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.name_fi') }}</label>
    <input type="text" name="name_fi" class="form-control" value="{{ $category['name_fi'] ?? '' }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.description_en') }}</label>
    <textarea name="description_en" class="form-control" rows="3">{{ $category['description_en'] ?? $category['description'] }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.description_fi') }}</label>
    <textarea name="description_fi" class="form-control" rows="3">{{ $category['description_fi'] ?? '' }}</textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">{{ \App\\Utils\\UIStrings::t('field.display_order') }}</label>
    <input type="number" name="displayOrder" class="form-control" value="{{ $category['displayOrder'] }}" min="0">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">{{ \App\\Utils\\UIStrings::t('common.cancel') }}</a>
  <button class="btn btn-primary">{{ \App\\Utils\\UIStrings::t('common.update') }}</button>
</form>
@endsection
