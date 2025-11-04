@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>{{ \App\Utils\UIStrings::t('sizes.index.title') }}</h2>
  <div class="d-flex align-items-center" style="gap:.5rem;">
    <a href="{{ route('menu.sizes.create') }}" class="btn btn-primary">{{ \App\Utils\UIStrings::t('sizes.add') }}</a>
  </div>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(!empty($sizes))
<table class="table table-striped">
  <thead>
    <tr>
      @if(!empty($allBranches))
      <th>{{ \App\Utils\UIStrings::t('branch') }}</th>
      @endif
      <th>{{ \App\Utils\UIStrings::t('common.name') }}</th>
      <th style="width:160px">{{ \App\Utils\UIStrings::t('common.price') }}</th>
      <th style="width:120px">{{ \App\Utils\UIStrings::t('common.active') }}</th>
      <th style="width:200px"></th>
    </tr>
  </thead>
  <tbody>
    @foreach($sizes as $s)
      <tr>
        @if(!empty($allBranches))
        <td>{{ $s['branchName'] ?? '' }}</td>
        @endif
        <td>{{ $s['name'] }}</td>
        <td>{{ number_format($s['price'], 2) }}</td>
        <td>{{ $s['isActive'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
        <td class="text-end">
          @php($role = session('role'))
          @if(($role === 'admin' || $role === 'restaurant_admin') || ($role === 'branch_admin' && empty($allBranches) && session('branchId')))
            @if(!empty($allBranches))
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.sizes.edit', $s['id']) }}?branchId={{ urlencode($s['branchId']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
              <form action="{{ route('menu.sizes.destroy', $s['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('sizes.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="branchId" value="{{ $s['branchId'] }}">
                <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
              </form>
            @else
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.sizes.edit', $s['id']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
              <form action="{{ route('menu.sizes.destroy', $s['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('sizes.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
              </form>
            @endif
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@else
  <p>{{ \App\Utils\UIStrings::t('sizes.none') }}</p>
@endif
@endsection
