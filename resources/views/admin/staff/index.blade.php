@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>{{ \App\Utils\UIStrings::t('staff.title') }}</h2>
  <a href="{{ route('staff.create') }}" class="btn btn-primary">{{ \App\Utils\UIStrings::t('staff.add') }}</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(($mode ?? 'single') === 'single')
  @if(empty($staff))
    <p>{{ \App\Utils\UIStrings::t('staff.none') }}</p>
  @else
  <table class="table table-striped">
    <thead>
      <tr>
        <th>{{ \App\Utils\UIStrings::t('staff.name') }}</th>
        <th>{{ \App\Utils\UIStrings::t('staff.email') }}</th>
        <th>{{ \App\Utils\UIStrings::t('staff.role') }}</th>
        <th>{{ \App\Utils\UIStrings::t('staff.active') }}</th>
        <th style="width:200px"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($staff as $s)
        <tr>
          <td>{{ $s['name'] }}</td>
          <td>{{ $s['email'] }}</td>
          <td>{{ $s['role'] }}</td>
          <td>{{ $s['isActive'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('staff.edit', $s['id']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
            <form action="{{ route('staff.destroy', $s['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('staff.delete_confirm') }}')">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @endif
@else
  @if(empty($branchStaff))
    <p>{{ \App\Utils\UIStrings::t('staff.none_branch') }}</p>
  @else
    @foreach($branchStaff as $bs)
      <h5 class="mt-4 mb-2">{{ \App\Utils\UIStrings::t('branch') }}: {{ $bs['branch']['name'] }}</h5>
      @if(empty($bs['staff']))
        <div class="text-muted small">{{ \App\Utils\UIStrings::t('staff.none') }}</div>
      @else
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ \App\Utils\UIStrings::t('staff.name') }}</th>
            <th>{{ \App\Utils\UIStrings::t('staff.email') }}</th>
            <th>{{ \App\Utils\UIStrings::t('staff.role') }}</th>
            <th>{{ \App\Utils\UIStrings::t('staff.active') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($bs['staff'] as $s)
            <tr>
              <td>{{ $s['name'] }}</td>
              <td>{{ $s['email'] }}</td>
              <td>{{ $s['role'] }}</td>
              <td>{{ $s['isActive'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    @endforeach
  @endif
@endif
@endsection
