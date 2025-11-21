@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Ingredients</h2>
  <a href="{{ route('menu.ingredients.create') }}" class="btn btn-primary">Add Ingredient</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(!empty($ingredients))
<div class="accordion" id="ingredientsAccordion">
  @foreach($ingredients as $idx => $ing)
  @php($collapseId = 'ing-'.$idx)
  <div class="card mb-2">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
            <i class="bi bi-chevron-down"></i>
          </button>
          <div>
            <div class="fw-semibold">{{ $ing['name'] }}</div>
            <div class="text-muted small">{{ $ing['description'] }}</div>
            @if(!empty($allBranches))
              <span class="badge bg-secondary">{{ $ing['branchName'] ?? '' }}</span>
            @endif
          </div>
        </div>
        <div class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.ingredients.edit', $ing['id']) }}@if(!empty($allBranches))?branchId={{ urlencode($ing['branchId']) }}@endif">Edit</a>
          <a class="btn btn-sm btn-outline-primary" href="{{ route('menu.ingredients.sub.create', $ing['id']) }}@if(!empty($allBranches))?branchId={{ urlencode($ing['branchId']) }}@endif">Add Sub</a>
          <form action="{{ route('menu.ingredients.destroy', $ing['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this ingredient?')">
            @csrf
            @method('DELETE')
            @if(!empty($allBranches))
            <input type="hidden" name="branchId" value="{{ $ing['branchId'] }}">
            @endif
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </div>
      </div>
      <div id="{{ $collapseId }}" class="collapse mt-3" data-bs-parent="#ingredientsAccordion">
        @if(!empty($ing['subs']))
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:30%">Name</th>
                <th>Description</th>
                <th style="width:140px">Price</th>
                @if(empty($allBranches))
                <th style="width:200px"></th>
                @endif
              </tr>
            </thead>
            <tbody>
              @foreach($ing['subs'] as $sub)
              <tr>
                <td>{{ $sub['name'] }}</td>
                <td class="text-muted small">{{ $sub['description'] }}</td>
                <td>{{ number_format($sub['price'], 2) }}</td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.ingredients.sub.edit', [$ing['id'], $sub['id']]) }}@if(!empty($allBranches))?branchId={{ urlencode($ing['branchId']) }}@endif">Edit</a>
                  <form action="{{ route('menu.ingredients.sub.destroy', [$ing['id'], $sub['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this sub-ingredient?')">
                    @csrf
                    @method('DELETE')
                    @if(!empty($allBranches))
                    <input type="hidden" name="branchId" value="{{ $ing['branchId'] }}">
                    @endif
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
          <div class="text-muted">No sub-ingredients.</div>
        @endif
      </div>
    </div>
  </div>
  @endforeach
</div>
@else
  <p>No ingredients yet.</p>
@endif
@endsection
