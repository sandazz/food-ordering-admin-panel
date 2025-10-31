@extends('layouts.admin')
@section('content')
<h2>Edit Staff</h2>
<form method="POST" action="{{ route('staff.update', $staff['id']) }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ $staff['name'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="{{ $staff['email'] }}" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
      @foreach($roles as $r)
        <option value="{{ $r }}" {{ $staff['role']===$r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Permissions</label>
    <div class="row">
      @foreach($permissions as $p)
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $p }}" id="p_{{ $p }}" {{ in_array($p, $staff['permissions']) ? 'checked' : '' }}>
            <label class="form-check-label" for="p_{{ $p }}">{{ $p }}</label>
          </div>
        </div>
      @endforeach
    </div>
  </div>
  <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" name="isActive" value="1" id="isActive" {{ $staff['isActive'] ? 'checked' : '' }}>
    <label class="form-check-label" for="isActive">Active</label>
  </div>
  <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Update</button>
</form>
@endsection
