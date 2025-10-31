@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Menu Management</h2>
  <a href="{{ route('menu.categories.create') }}" class="btn btn-primary">Add Category</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(($mode ?? 'single') === 'single')
  @if(empty($categories))
    <p>No categories yet.</p>
  @else
    @foreach($categories as $cat)
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">{{ $cat['name'] }}</h5>
              <div class="text-muted small">Order: {{ $cat['displayOrder'] }}</div>
              <div class="text-muted small">{{ $cat['description'] }}</div>
            </div>
            <div>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('menu.items.create', $cat['id']) }}">Add Item</a>
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.categories.edit', $cat['id']) }}">Edit</a>
              <a class="btn btn-sm btn-outline-success" href="{{ route('menu.categories.copy.form', $cat['id']) }}">Copy to Branches</a>
              <form action="{{ route('menu.categories.destroy', $cat['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          </div>
          @if(!empty($cat['items']))
            <table class="table table-sm mt-3">
              <thead>
                <tr>
                  <th>Name</th>
                  <th style="width:120px">Price</th>
                  <th style="width:100px">Available</th>
                  <th style="width:220px"></th>
                </tr>
              </thead>
              <tbody>
                @foreach($cat['items'] as $item)
                  <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>${{ number_format($item['price'], 2) }}</td>
                    <td>{{ $item['available'] ? 'Yes' : 'No' }}</td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.items.edit', [$cat['id'], $item['id']]) }}">Edit</a>
                      <form action="{{ route('menu.items.destroy', [$cat['id'], $item['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete item?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div class="text-muted small mt-2">No items.</div>
          @endif
        </div>
      </div>
    @endforeach
  @endif
@else
  @if(empty($branchMenus))
    <p>No menus found.</p>
  @else
    @foreach($branchMenus as $bm)
      <h5 class="mt-4 mb-2">Branch: {{ $bm['branch']['name'] }}</h5>
      @forelse($bm['categories'] as $cat)
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1">{{ $cat['name'] }}</h6>
                <div class="text-muted small">Order: {{ $cat['displayOrder'] }}</div>
                <div class="text-muted small">{{ $cat['description'] }}</div>
              </div>
            </div>
            @if(!empty($cat['items']))
              <table class="table table-sm mt-3">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th style="width:120px">Price</th>
                    <th style="width:100px">Available</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($cat['items'] as $item)
                    <tr>
                      <td>{{ $item['name'] }}</td>
                      <td>${{ number_format($item['price'], 2) }}</td>
                      <td>{{ $item['available'] ? 'Yes' : 'No' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <div class="text-muted small mt-2">No items.</div>
            @endif
          </div>
        </div>
      @empty
        <p class="text-muted">No categories.</p>
      @endforelse
    @endforeach
  @endif
@endif
@endsection
