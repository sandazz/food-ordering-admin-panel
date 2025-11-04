@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>{{ \App\Utils\UIStrings::t('bases.index.title') }}</h2>
  <a href="{{ route('menu.bases.create') }}" class="btn btn-primary">{{ \App\Utils\UIStrings::t('bases.add') }}</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(!empty($bases))
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
    @foreach($bases as $b)
      <tr>
        @if(!empty($allBranches))
        <td>{{ $b['branchName'] ?? '' }}</td>
        @endif
        <td>{{ $b['name'] }}</td>
        <td>{{ number_format($b['price'], 2) }}</td>
        <td>{{ $b['isActive'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
        <td class="text-end">
          @php($role = session('role'))
          @if(($role === 'admin' || $role === 'restaurant_admin') || ($role === 'branch_admin' && empty($allBranches) && session('branchId')))
            @if(!empty($allBranches))
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.bases.edit', $b['id']) }}?branchId={{ urlencode($b['branchId']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
              <form action="{{ route('menu.bases.destroy', $b['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('bases.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="branchId" value="{{ $b['branchId'] }}">
                <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
              </form>
            @else
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.bases.edit', $b['id']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
              <form action="{{ route('menu.bases.destroy', $b['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('bases.delete_confirm') }}')">
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
  <p>{{ \App\Utils\UIStrings::t('bases.none') }}</p>
@endif
@endsection
