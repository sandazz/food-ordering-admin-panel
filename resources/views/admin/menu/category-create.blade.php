@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('categories.create.title') }}</h2>
<form method="POST" action="{{ route('menu.categories.store') }}" class="mt-3">
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
  <div class="mb-3">
    <label class="form-label">{{ \App\Utils\UIStrings::t('field.display_order') }}</label>
    <input type="number" name="displayOrder" class="form-control" value="0" min="0">
  </div>
  <a href="{{ route('menu.index') }}" class="btn btn-outline-secondary">{{ \App\Utils\UIStrings::t('common.cancel') }}</a>
  <button class="btn btn-primary">{{ \App\Utils\UIStrings::t('common.save') }}</button>
</form>
@endsection
