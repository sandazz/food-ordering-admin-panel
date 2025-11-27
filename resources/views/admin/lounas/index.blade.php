@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Lounas Hour</h2>
  <a href="{{ route('lounas.create') }}" class="btn btn-primary">Add Time Range</a>
 </div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(isset($mode) && $mode === 'single')
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Day</th>
        <th>Start</th>
        <th>End</th>
        <th>Active</th>
        <th style="width:220px"></th>
      </tr>
    </thead>
    <tbody>
      @forelse(($items ?? []) as $it)
        <tr>
          <td>{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$it['dayOfWeek']] }}</td>
          <td>{{ $it['startTime'] }}</td>
          <td>{{ $it['endTime'] }}</td>
          <td>{{ $it['active'] ? 'Yes' : 'No' }}</td>
          <td class="text-end">
            <a href="{{ route('lounas.edit', [$restaurantId, $currentBranchId, $it['id']]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            <form action="{{ route('lounas.destroy', [$restaurantId, $currentBranchId, $it['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this time range?')">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center text-muted">No lounas hours configured.</td></tr>
      @endforelse
    </tbody>
  </table>
@elseif(isset($mode) && $mode === 'all')
  @foreach(($branchItems ?? []) as $bi)
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Branch: {{ $bi['branch']['name'] }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
                <th>Active</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bi['items'] as $it)
              <tr>
                <td>{{ ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$it['dayOfWeek']] }}</td>
                <td>{{ $it['startTime'] }}</td>
                <td>{{ $it['endTime'] }}</td>
                <td>{{ $it['active'] ? 'Yes' : 'No' }}</td>
                <td>
                  <a href="{{ route('lounas.edit', [$restaurantId, $bi['branch']['id'], $it['id']]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                  <form action="{{ route('lounas.destroy', [$restaurantId, $bi['branch']['id'], $it['id']]) }}" method="post" style="display:inline-block" onsubmit="return confirm('Delete this time range?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted">No lounas hours.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endforeach
@endif
@endsection
