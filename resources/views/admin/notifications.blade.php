@extends('layouts.admin')
@section('content')
<h2>Notification Management</h2>
<form method="POST" action="{{ route('notifications.send') }}">
    @csrf
    <div style="display:grid;gap:12px;max-width:760px;">
        <div style="display:grid;gap:4px;">
            <label>Title</label>
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" name="title" id="title" required maxlength="120" style="flex:1;" />
                <small id="titleCount" style="color:#6b7280;min-width:80px;text-align:right;">0 / 120</small>
            </div>
        </div>
        <div style="display:grid;gap:4px;">
            <label>Body</label>
            <div style="display:flex;gap:8px;">
                <textarea name="body" id="body" required maxlength="500" rows="4" style="flex:1;"></textarea>
                <small id="bodyCount" style="color:#6b7280;min-width:80px;text-align:right;">0 / 500</small>
            </div>
        </div>

        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
            <div style="display:flex;gap:0;border-bottom:1px solid #e5e7eb;">
                <button type="button" id="tabToken" class="tab active" onclick="switchTab('token')" style="padding:8px 12px;border:0;background:#ffffff;border-right:1px solid #e5e7eb;">By Token</button>
                <button type="button" id="tabTopics" class="tab" onclick="switchTab('topics')" style="padding:8px 12px;border:0;background:#f3f4f6;">By Topics</button>
            </div>
            <div id="panelToken" style="padding:12px;display:block;">
                <div style="display:grid;gap:8px;">
                    <label>Device Token
                        <input type="text" name="token" id="tokenInput" placeholder="FCM device token" />
                    </label>
                    <small style="color:#6b7280;">When a token is provided, topics will be ignored.</small>
                </div>
            </div>
            <div id="panelTopics" style="padding:12px;display:none;">
                <div style="display:grid;gap:8px;grid-template-columns:repeat(2, minmax(0,1fr));">
                    <label>Restaurant ID
                        <input type="text" name="restaurantId" id="restaurantId" placeholder="restaurant_xxx" />
                    </label>
                    <label>Region
                        <input type="text" name="region" id="region" placeholder="e.g. NYC" />
                    </label>
                    <label>Group
                        <input type="text" name="group" id="group" placeholder="e.g. vip" />
                    </label>
                    <label>Custom Topic
                        <input type="text" name="topic" id="topic" placeholder="custom_topic" />
                    </label>
                </div>
                <small style="color:#6b7280;display:block;margin-top:6px;">Messages will be sent to all provided topics.</small>
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit">Send Notification</button>
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
