@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Branches</h2>
  <a href="{{ route('settings.branches.create', $restaurantId) }}" class="btn btn-primary">Add Branch</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(empty($branches))
  <p>No branches yet.</p>
@else
<table class="table table-striped">
  <thead>
    <tr>
      <th>Name</th>
      <th>Status</th>
      <th style="width:240px"></th>
    </tr>
  </thead>
  <tbody>
    @foreach($branches as $b)
      <tr>
        <td>{{ $b['name'] }}</td>
        <td>{{ $b['status'] }}</td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="{{ route('settings.branches.edit', [$restaurantId, $b['id']]) }}">Edit</a>
          <form action="{{ route('settings.branches.destroy', [$restaurantId, $b['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete branch?')">
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
@endsection
