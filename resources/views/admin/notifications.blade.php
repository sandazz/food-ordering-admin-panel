@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('notifications.title') }}</h2>
<form method="POST" action="{{ route('notifications.send') }}">
    @csrf
    <div style="display:grid;gap:12px;max-width:760px;">
        <div style="display:grid;gap:4px;">
            <label>{{ \App\Utils\UIStrings::t('notifications.title_label') }}</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" name="title" id="title" required maxlength="120" style="flex:1;" />
                <small id="titleCount" style="color:#6b7280;min-width:80px;text-align:right;">0 / 120</small>
            </div>
        </div>
        <div style="display:grid;gap:4px;">
            <label>{{ \App\Utils\UIStrings::t('notifications.body_label') }}</label>
            <div style="display:flex;gap:8px;">
                <textarea name="body" id="body" required maxlength="500" rows="4" style="flex:1;"></textarea>
                <small id="bodyCount" style="color:#6b7280;min-width:80px;text-align:right;">0 / 500</small>
            </div>
        </div>

        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
            <div style="display:flex;gap:0;border-bottom:1px solid #e5e7eb;">
                <button type="button" id="tabToken" class="tab active" onclick="switchTab('token')" style="padding:8px 12px;border:0;background:#ffffff;border-right:1px solid #e5e7eb;">{{ \App\Utils\UIStrings::t('notifications.by_token') }}</button>
                <button type="button" id="tabTopics" class="tab" onclick="switchTab('topics')" style="padding:8px 12px;border:0;background:#f3f4f6;">{{ \App\Utils\UIStrings::t('notifications.by_topics') }}</button>
            </div>
            <div id="panelToken" style="padding:12px;display:block;">
                <div style="display:grid;gap:8px;">
                    <label>{{ \App\Utils\UIStrings::t('notifications.device_token') }}
                        <input type="text" name="token" id="tokenInput" placeholder="{{ \App\Utils\UIStrings::t('notifications.device_token_placeholder') }}" />
                    </label>
                    <small style="color:#6b7280;">{{ \App\Utils\UIStrings::t('notifications.token_hint') }}</small>
                </div>
            </div>
            <div id="panelTopics" style="padding:12px;display:none;">
                <div style="display:grid;gap:8px;grid-template-columns:repeat(2, minmax(0,1fr));">
                    <label>{{ \App\Utils\UIStrings::t('notifications.restaurant_id') }}
                        <input type="text" name="restaurantId" id="restaurantId" placeholder="restaurant_xxx" />
                    </label>
                    <label>{{ \App\Utils\UIStrings::t('notifications.region') }}
                        <input type="text" name="region" id="region" placeholder="e.g. NYC" />
                    </label>
                    <label>{{ \App\Utils\UIStrings::t('notifications.group') }}
                        <input type="text" name="group" id="group" placeholder="e.g. vip" />
                    </label>
                    <label>{{ \App\Utils\UIStrings::t('notifications.custom_topic') }}
                        <input type="text" name="topic" id="topic" placeholder="custom_topic" />
                    </label>
                </div>
                <small style="color:#6b7280;display:block;margin-top:6px;">{{ \App\Utils\UIStrings::t('notifications.topics_hint') }}</small>
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" id="sendBtn">{{ \App\Utils\UIStrings::t('notifications.send') }}</button>
        </div>
    </div>

    @if (session('status'))
        <div style="margin-top:8px;color:green;">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div style="margin-top:8px;color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</form>

<script>
function btnStart(btn, text){ if(!btn) return; btn.disabled = true; btn.dataset._orig = btn.innerHTML; btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:-2px;"></span> ' + (text||'Processing...'); }
function btnDone(btn){ if(!btn) return; btn.disabled = false; if(btn.dataset._orig){ btn.innerHTML = btn.dataset._orig; delete btn.dataset._orig; } }
document.querySelector('form[action="{{ route('notifications.send') }}"]').addEventListener('submit', function(){ const b=document.getElementById('sendBtn'); btnStart(b,'Sending...'); setTimeout(()=>btnDone(b), 1200); });
function updateCount(id, outId, max){
  const v = document.getElementById(id).value.length; document.getElementById(outId).textContent = v + ' / ' + max;
}
document.getElementById('title').addEventListener('input', ()=>updateCount('title','titleCount',120));
document.getElementById('body').addEventListener('input', ()=>updateCount('body','bodyCount',500));
updateCount('title','titleCount',120); updateCount('body','bodyCount',500);

function switchTab(which){
  const isToken = which === 'token';
  document.getElementById('panelToken').style.display = isToken ? 'block' : 'none';
  document.getElementById('panelTopics').style.display = isToken ? 'none' : 'block';
  document.getElementById('tabToken').style.background = isToken ? '#ffffff' : '#f3f4f6';
  document.getElementById('tabTopics').style.background = !isToken ? '#ffffff' : '#f3f4f6';
}

document.getElementById('tokenInput').addEventListener('input', (e)=>{
  const hasToken = !!e.target.value.trim();
  ['restaurantId','region','group','topic'].forEach(id=>{
    document.getElementById(id).disabled = hasToken;
  });
});
</script>
@endsection
