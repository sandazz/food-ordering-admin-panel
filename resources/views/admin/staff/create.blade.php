@extends('layouts.admin')
@section('content')
<h2>Create Staff</h2>
<form method="POST" action="{{ route('staff.store') }}" class="mt-3">
  @csrf
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
      @foreach($roles as $r)
        <option value="{{ $r }}">{{ ucfirst(str_replace('_',' ',$r)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Permissions</label>
    <div class="row">
      @foreach($permissions as $p)
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $p }}" id="p_{{ $p }}">
            <label class="form-check-label" for="p_{{ $p }}">{{ $p }}</label>
          </div>
        </div>
      @endforeach
    </div>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="isActive" value="1" checked id="isActive">
    <label class="form-check-label" for="isActive">Active</label>
  </div>
  <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
@endsection
