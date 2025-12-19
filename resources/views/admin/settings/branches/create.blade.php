@extends('layouts.admin')
@section('content')
<h2>Create Branch</h2>
<form method="POST" action="{{ route('settings.branches.store', $restaurantId) }}" class="mt-3" enctype="multipart/form-data">
  @csrf
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Contact</label>
      <input type="text" name="contact" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select" required>
        <option value="open">Open</option>
        <option value="closed">Closed</option>
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Business ID</label>
      <input type="text" name="businessId" class="form-control" value="{{ old('businessId') }}" placeholder="e.g. 123-456-789">
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Street</label>
      <input type="text" name="street" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">City</label>
      <input type="text" name="city" class="form-control" required>
    </div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label">State</label>
      <input type="text" name="state" class="form-control" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Zip Code</label>
      <input type="text" name="zipCode" class="form-control" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Country</label>
      <input type="text" name="country" class="form-control" required>
    </div>
  </div>
  <div class="row">
    <div class="col-md-3 mb-3">
      <label class="form-label">Tax Rate</label>
      <input type="number" step="0.01" name="taxRate" class="form-control" value="0">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Service Charge</label>
      <input type="number" step="0.01" name="serviceCharge" class="form-control" value="0">
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Upload Image</label>
      <input type="file" name="image" id="branchImageInput" class="form-control" accept="image/*" required>
      <div class="mt-2">
        <img id="branchImagePreview" alt="Branch Image" style="max-height:80px;display:none;"/>
      </div>
    </div>
  </div>
  <script>
    (function(){
      const inp = document.getElementById('branchImageInput');
      const img = document.getElementById('branchImagePreview');
      function bind(){
        if(!(inp && img)) return;
        inp.addEventListener('change', function(){
          const f = this.files && this.files[0];
          if(f){ img.src = URL.createObjectURL(f); img.style.display=''; }
        });
      }
      if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', bind);
      } else { bind(); }
    })();
  </script>
  <hr />
  <h4>Payment Gateway (optional)</h4>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Gateway Name</label>
      <input type="text" name="gateway_name" class="form-control" value="{{ old('gateway_name', 'Paytrail') }}">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Merchant ID</label>
      <input type="text" name="merchant_id" class="form-control" value="{{ old('merchant_id') }}" placeholder="Paytrail merchant id">
    </div>
  </div>
  <div class="row">
    <div class="col-md-8 mb-3">
      <label class="form-label">Secret Key</label>
      <div class="input-group">
        <input id="createSecretInput" type="password" name="secret_key" class="form-control" value="" placeholder="Enter secret key">
        <button id="createSecretToggle" type="button" class="btn btn-outline-secondary" aria-label="Toggle secret visibility">
          <!-- eye icon -->
          <svg id="createIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Enabled</label>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
        <label class="form-check-label">Active</label>
      </div>
    </div>
  </div>
  <a href="{{ route('settings.branches', $restaurantId) }}" class="btn btn-outline-secondary">Cancel</a>
  <button class="btn btn-primary">Save</button>
</form>
<script>
  (function(){
    function bindToggle(inputId, btnId, iconId){
      const f = document.getElementById(inputId);
      const b = document.getElementById(btnId);
      const icon = document.getElementById(iconId);
      if(!f || !b || !icon) return;
      const eyeSvg = '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>';
      const eyeSlashSvg = '<path d="M13.359 11.238C11.852 12.457 10.03 13 8 13c-5 0-8-5-8-5 .8-1.189 1.843-2.172 3.08-2.998L2.2 3.995 1 5.195 3.264 7.46C2.489 8.217 1.839 8.99 1.362 9.605 2.91 11.481 5.313 13 8 13c2.03 0 3.852-.543 5.359-1.762l-.0 0z"/><path d="M11.95 4.05a7.24 7.24 0 0 1 1.354 1.386c-.695.56-1.46 1.12-2.285 1.64L4.36 2.34 5.64 1.06l6.31 2.99z"/>';
      b.addEventListener('click', function(){
        if(f.type === 'password'){
          f.type = 'text';
          // eye -> eye-slash
          icon.innerHTML = eyeSlashSvg;
        } else {
          f.type = 'password';
          icon.innerHTML = eyeSvg;
        }
      });
    }
    if(document.readyState === 'loading'){
      document.addEventListener('DOMContentLoaded', function(){ bindToggle('createSecretInput','createSecretToggle','createIcon'); });
    } else { bindToggle('createSecretInput','createSecretToggle','createIcon'); }
  })();
</script>
@endsection
