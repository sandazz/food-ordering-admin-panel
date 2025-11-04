@extends('layouts.admin')
@section('content')
<h2>System Settings</h2>
@if (session('status'))
    <div style="margin:8px 0;color:green;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div style="margin:8px 0;color:red;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(session('role') === 'admin')
<form method="POST" action="{{ route('settings.system.save') }}" style="display:grid;gap:12px;max-width:720px;">
    @csrf
    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>Payments</legend>
        <label>Gateway
            <input type="text" name="payments[gateway]" value="{{ $settings['payments']['gateway'] ?? '' }}" required />
        </label>
        <label>
            <input type="hidden" name="payments[enabled]" value="0" />
            <input type="checkbox" name="payments[enabled]" value="1" {{ !empty($settings['payments']['enabled']) ? 'checked' : '' }} /> Enabled
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>Features</legend>
        <label>
            <input type="hidden" name="features[delivery]" value="0" />
            <input type="checkbox" name="features[delivery]" value="1" {{ !empty($settings['features']['delivery']) ? 'checked' : '' }} /> Delivery
        </label>
        <label>
            <input type="hidden" name="features[pickup]" value="0" />
            <input type="checkbox" name="features[pickup]" value="1" {{ !empty($settings['features']['pickup']) ? 'checked' : '' }} /> Pickup
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>Pricing Defaults</legend>
        <label>Tax Rate (%)
            <input type="number" step="0.01" min="0" name="pricing[taxRate]" value="{{ $settings['pricing']['taxRate'] ?? 0 }}" required />
        </label>
        <label>Service Charge (%)
            <input type="number" step="0.01" min="0" name="pricing[serviceCharge]" value="{{ $settings['pricing']['serviceCharge'] ?? 0 }}" required />
        </label>
        <label>Delivery Fee
            <input type="number" step="0.01" min="0" name="pricing[deliveryFee]" value="{{ $settings['pricing']['deliveryFee'] ?? 0 }}" required />
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>Localization</legend>
        <label>Default Locale
            <input type="text" name="localization[default_locale]" value="{{ $settings['localization']['default_locale'] ?? 'en' }}" required />
        </label>
        <label>Locales (comma separated)
            <input type="text" id="localesInput" value="{{ isset($settings['localization']['locales']) ? implode(',', $settings['localization']['locales']) : 'en' }}" />
        </label>
        <div id="localesHidden"></div>
        <script>
            function syncLocales() {
                const wrap = document.getElementById('localesHidden');
                wrap.innerHTML = '';
                const raw = document.getElementById('localesInput').value.split(',').map(s=>s.trim()).filter(Boolean);
                raw.forEach((v,i)=>{
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'localization[locales]['+i+']';
                    inp.value = v;
                    wrap.appendChild(inp);
                });
            }
            document.getElementById('localesInput').addEventListener('input', syncLocales);
            syncLocales();
        </script>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>GDPR</legend>
        <label>
            <input type="hidden" name="gdpr[consent_required]" value="0" />
            <input type="checkbox" name="gdpr[consent_required]" value="1" {{ !empty($settings['gdpr']['consent_required']) ? 'checked' : '' }} /> Consent required
        </label>
        <label>Retention Days
            <input type="number" min="0" name="gdpr[retention_days]" value="{{ $settings['gdpr']['retention_days'] ?? 0 }}" />
        </label>
        <div style="margin-top:8px;display:flex;gap:8px;">
            <form method="POST" action="{{ route('settings.gdpr.delete_user') }}">
                @csrf
                <input type="text" name="userId" placeholder="User ID" />
                <input type="email" name="email" placeholder="User Email (optional)" />
                <button type="submit">Delete User Data</button>
            </form>
            <a href="{{ route('settings.gdpr.consents.export') }}" class="button">Export Consent Logs (CSV)</a>
        </div>
    </fieldset>

    <button type="submit">Save Settings</button>
</form>
@else
  <div class="alert alert-info">You do not have permission to view or modify system settings.</div>
@endif
@endsection
