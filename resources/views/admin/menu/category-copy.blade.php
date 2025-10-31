@extends('layouts.admin')
@section('content')
<h2>Copy Category</h2>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
<p>Category: <strong>{{ $categoryName }}</strong></p>
@if (empty($branches))
  <div class="alert alert-info">No other branches available.</div>
@else
  <form method="POST" action="{{ route('menu.categories.copy', $categoryId) }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Select branches to copy into</label>
      <div class="border rounded p-2" style="max-width:520px;">
        @foreach($branches as $b)
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="targets[]" value="{{ $b['id'] }}" id="b_{{ $b['id'] }}">
            <label class="form-check-label" for="b_{{ $b['id'] }}">{{ $b['name'] }}</label>
          </div>
        @endforeach
      </div>
    </div>
    <button class="btn btn-primary">Copy</button>
  </form>
@endif
@endsection
