@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Staff</h2>
  <a href="{{ route('staff.create') }}" class="btn btn-primary">Add Staff</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(($mode ?? 'single') === 'single')
  @if(empty($staff))
    <p>No staff yet.</p>
  @else
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Active</th>
        <th style="width:200px"></th>
      </tr>
    </thead>
    <tbody>
      @foreach($staff as $s)
        <tr>
          <td>{{ $s['name'] }}</td>
          <td>{{ $s['email'] }}</td>
          <td>{{ $s['role'] }}</td>
          <td>{{ $s['isActive'] ? 'Yes' : 'No' }}</td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('staff.edit', $s['id']) }}">Edit</a>
            <form action="{{ route('staff.destroy', $s['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete staff?')">
              @csrf
              @method('DELETE')
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @endif
@else
  @if(empty($branchStaff))
    <p>No staff found.</p>
  @else
    @foreach($branchStaff as $bs)
      <h5 class="mt-4 mb-2">Branch: {{ $bs['branch']['name'] }}</h5>
      @if(empty($bs['staff']))
        <div class="text-muted small">No staff.</div>
      @else
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Active</th>
          </tr>
        </thead>
        <tbody>
          @foreach($bs['staff'] as $s)
            <tr>
              <td>{{ $s['name'] }}</td>
              <td>{{ $s['email'] }}</td>
              <td>{{ $s['role'] }}</td>
              <td>{{ $s['isActive'] ? 'Yes' : 'No' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    @endforeach
  @endif
@endif
@endsection
