@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Restaurants</h2>
  <a href="{{ route('settings.restaurants.create') }}" class="btn btn-primary">Add Restaurant</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(empty($restaurants))
  <p>No restaurants yet.</p>
@else
<table class="table table-striped">
  <thead>
    <tr>
      <th>Name</th>
      <th>Status</th>
      <th style="width:260px"></th>
    </tr>
  </thead>
  <tbody>
    @foreach($restaurants as $r)
      <tr>
        <td>{{ $r['name'] }}</td>
        <td>{{ $r['status'] }}</td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="{{ route('settings.restaurants.edit', $r['id']) }}">Edit</a>
          <a class="btn btn-sm btn-outline-primary" href="{{ route('settings.branches', $r['id']) }}">Branches</a>
          <form action="{{ route('settings.restaurants.destroy', $r['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete restaurant?')">
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
