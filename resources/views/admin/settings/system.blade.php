@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('settings.title') }}</h2>
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
        <legend>{{ \App\Utils\UIStrings::t('settings.payments') }}</legend>
        <label>{{ \App\Utils\UIStrings::t('settings.payments.gateway') }}
            <input type="text" name="payments[gateway]" value="{{ $settings['payments']['gateway'] ?? '' }}" required />
        </label>
        <label>
            <input type="hidden" name="payments[enabled]" value="0" />
            <input type="checkbox" name="payments[enabled]" value="1" {{ !empty($settings['payments']['enabled']) ? 'checked' : '' }} /> {{ \App\Utils\UIStrings::t('settings.enabled') }}
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>{{ \App\Utils\UIStrings::t('settings.features') }}</legend>
        <label>
            <input type="hidden" name="features[delivery]" value="0" />
            <input type="checkbox" name="features[delivery]" value="1" {{ !empty($settings['features']['delivery']) ? 'checked' : '' }} /> {{ \App\Utils\UIStrings::t('settings.delivery') }}
        </label>
        <label>
            <input type="hidden" name="features[pickup]" value="0" />
            <input type="checkbox" name="features[pickup]" value="1" {{ !empty($settings['features']['pickup']) ? 'checked' : '' }} /> {{ \App\Utils\UIStrings::t('settings.pickup') }}
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>{{ \App\Utils\UIStrings::t('settings.pricing_defaults') }}</legend>
        <label>{{ \App\Utils\UIStrings::t('settings.tax_rate') }}
            <input type="number" step="0.01" min="0" name="pricing[taxRate]" value="{{ $settings['pricing']['taxRate'] ?? 0 }}" required />
        </label>
        <label>{{ \App\Utils\UIStrings::t('settings.service_charge') }}
            <input type="number" step="0.01" min="0" name="pricing[serviceCharge]" value="{{ $settings['pricing']['serviceCharge'] ?? 0 }}" required />
        </label>
        <label>{{ \App\Utils\UIStrings::t('settings.delivery_fee') }}
            <input type="number" step="0.01" min="0" name="pricing[deliveryFee]" value="{{ $settings['pricing']['deliveryFee'] ?? 0 }}" required />
        </label>
    </fieldset>

    <fieldset style="border:1px solid #e5e7eb;padding:12px;">
        <legend>{{ \App\Utils\UIStrings::t('settings.localization') }}</legend>
        <label>{{ \App\Utils\UIStrings::t('settings.default_locale') }}
            <input type="text" name="localization[default_locale]" value="{{ $settings['localization']['default_locale'] ?? 'en' }}" required />
        </label>
        <label>{{ \App\Utils\UIStrings::t('settings.locales_csv') }}
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
        <legend>{{ \App\Utils\UIStrings::t('settings.gdpr') }}</legend>
        <label>
            <input type="hidden" name="gdpr[consent_required]" value="0" />
            <input type="checkbox" name="gdpr[consent_required]" value="1" {{ !empty($settings['gdpr']['consent_required']) ? 'checked' : '' }} /> {{ \App\Utils\UIStrings::t('settings.consent_required') }}
        </label>
        <label>{{ \App\Utils\UIStrings::t('settings.retention_days') }}
            <input type="number" min="0" name="gdpr[retention_days]" value="{{ $settings['gdpr']['retention_days'] ?? 0 }}" />
        </label>
        <div style="margin-top:8px;display:flex;gap:8px;">
            <form method="POST" action="{{ route('settings.gdpr.delete_user') }}">
                @csrf
                <input type="text" name="userId" placeholder="{{ \App\Utils\UIStrings::t('settings.user_id') }}" />
                <input type="email" name="email" placeholder="{{ \App\Utils\UIStrings::t('settings.user_email_optional') }}" />
                <button type="submit">{{ \App\Utils\UIStrings::t('settings.delete_user_data') }}</button>
            </form>
            <a href="{{ route('settings.gdpr.consents.export') }}" class="button">{{ \App\Utils\UIStrings::t('settings.export_consent_logs') }}</a>
        </div>
    </fieldset>

    <button type="submit">{{ \App\Utils\UIStrings::t('settings.save_settings') }}</button>
</form>
@else
  <div class="alert alert-info">{{ \App\Utils\UIStrings::t('settings.no_permission') }}</div>
@endif
@endsection
