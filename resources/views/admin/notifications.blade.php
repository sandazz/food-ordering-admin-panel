@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ \App\Utils\UIStrings::t('notifications.title') }}</h1>
        <p class="page-subtitle">Send push notifications to your customers and staff</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#notificationHistory">
            <i class="bi bi-clock-history me-2"></i>History
        </button>
        <button class="btn btn-outline-secondary" onclick="loadTemplates()">
            <i class="bi bi-bookmarks me-2"></i>Templates
        </button>
    </div>
</div>

<!-- Notification Statistics -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card primary h-100">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-bell"></i>
                </div>
                <h4 class="mb-1">1,247</h4>
                <p class="text-muted mb-0">Total Sent Today</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card success h-100">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h4 class="mb-1">98.5%</h4>
                <p class="text-muted mb-0">Delivery Rate</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card warning h-100">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-hand-thumbs-up"></i>
                </div>
                <h4 class="mb-1">76.2%</h4>
                <p class="text-muted mb-0">Engagement Rate</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stats-card info h-100">
            <div class="card-body text-center">
                <div class="icon mx-auto">
                    <i class="bi bi-people"></i>
                </div>
                <h4 class="mb-1">4,521</h4>
                <p class="text-muted mb-0">Active Devices</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Notification Form -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-send me-2"></i>Compose Notification
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('notifications.send') }}" id="notificationForm">
                    @csrf
                    
                    <!-- Notification Content -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.title_label') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-card-heading"></i>
                                </span>
                                <input type="text" name="title" id="title" class="form-control" 
                                       required maxlength="120" placeholder="Enter notification title..." />
                                <span class="input-group-text">
                                    <small id="titleCount" class="text-muted">0/120</small>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.body_label') }}</label>
                            <div class="position-relative">
                                <textarea name="body" id="body" class="form-control" required maxlength="500" 
                                          rows="4" placeholder="Write your notification message here..."></textarea>
                                <span class="position-absolute bottom-0 end-0 m-2">
                                    <small id="bodyCount" class="text-muted">0/500</small>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Targeting Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="targetingTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#deviceTarget" onclick="switchTab('token')">
                                        <i class="bi bi-phone me-2"></i>{{ \App\Utils\UIStrings::t('notifications.by_token') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#topicTarget" onclick="switchTab('topics')">
                                        <i class="bi bi-tags me-2"></i>{{ \App\Utils\UIStrings::t('notifications.by_topics') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Device Token Tab -->
                                <div class="tab-pane fade show active" id="deviceTarget">
                                    <div class="mb-3">
                                        <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.device_token') }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-phone"></i>
                                            </span>
                                            <input type="text" name="token" id="tokenInput" class="form-control" 
                                                   placeholder="{{ \App\Utils\UIStrings::t('notifications.device_token_placeholder') }}" />
                                            <button type="button" class="btn btn-outline-secondary" onclick="scanQR()">
                                                <i class="bi bi-qr-code"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            {{ \App\Utils\UIStrings::t('notifications.token_hint') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Topics Tab -->
                                <div class="tab-pane fade" id="topicTarget">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.restaurant_id') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-shop"></i>
                                                </span>
                                                <input type="text" name="restaurantId" id="restaurantId" class="form-control" 
                                                       placeholder="restaurant_xxx" />
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.region') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-geo-alt"></i>
                                                </span>
                                                <input type="text" name="region" id="region" class="form-control" 
                                                       placeholder="e.g. NYC" />
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.group') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-people"></i>
                                                </span>
                                                <input type="text" name="group" id="group" class="form-control" 
                                                       placeholder="e.g. vip" />
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">{{ \App\Utils\UIStrings::t('notifications.custom_topic') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-tag"></i>
                                                </span>
                                                <input type="text" name="topic" id="topic" class="form-control" 
                                                       placeholder="custom_topic" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text mt-3">
                                        <i class="bi bi-info-circle me-1"></i>
                                        {{ \App\Utils\UIStrings::t('notifications.topics_hint') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
                                <i class="bi bi-gear me-2"></i>Advanced Options
                                <i class="bi bi-chevron-down ms-2"></i>
                            </button>
                        </div>
                        <div class="collapse" id="advancedOptions">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Priority</label>
                                        <select class="form-select">
                                            <option value="normal">Normal</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Schedule</label>
                                        <input type="datetime-local" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Expiry</label>
                                        <select class="form-select">
                                            <option value="1">1 hour</option>
                                            <option value="24">24 hours</option>
                                            <option value="168">1 week</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="soundEnabled">
                                            <label class="form-check-label" for="soundEnabled">
                                                Enable Sound
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="vibrateEnabled">
                                            <label class="form-check-label" for="vibrateEnabled">
                                                Enable Vibration
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                            <i class="bi bi-save me-2"></i>Save Draft
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="previewNotification()">
                            <i class="bi bi-eye me-2"></i>Preview
                        </button>
                        <button type="submit" class="btn btn-primary" id="sendBtn">
                            <i class="bi bi-send me-2"></i>{{ \App\Utils\UIStrings::t('notifications.send') }}
                        </button>
                    </div>
                </form>

                <!-- Status Messages -->
                @if (session('status'))
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Sidebar Widgets -->
    <div class="col-lg-4">
        <div class="row g-3">
            <!-- Preview Panel -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-phone me-2"></i>Live Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="notification-preview bg-light p-3 rounded">
                            <div class="d-flex align-items-start gap-2">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; min-width: 24px;">
                                    <i class="bi bi-shop text-white" style="font-size: 12px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1" id="previewTitle">Notification Title</h6>
                                    <p class="mb-0 text-muted small" id="previewBody">Your notification message will appear here...</p>
                                    <small class="text-muted">now</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Templates -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Templates
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('order_ready')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Order Ready</h6>
                                        <small class="text-muted">Customer pickup notification</small>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('promotion')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Special Offer</h6>
                                        <small class="text-muted">Promotional notification</small>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('reminder')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Order Reminder</h6>
                                        <small class="text-muted">Gentle reminder to order</small>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('welcome')">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Welcome Message</h6>
                                        <small class="text-muted">New customer welcome</small>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Recent Notifications
                        </h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">Your order is ready!</h6>
                                        <small class="text-muted">Sent to 45 devices</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">98% delivered</span>
                                        <small class="text-muted d-block">2 hours ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">50% off weekend special</h6>
                                        <small class="text-muted">Sent to 1,234 devices</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">95% delivered</span>
                                        <small class="text-muted d-block">1 day ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">New menu items available</h6>
                                        <small class="text-muted">Sent to 2,156 devices</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">97% delivered</span>
                                        <small class="text-muted d-block">3 days ago</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification History Modal -->
<div class="modal fade" id="notificationHistory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Recipients</th>
                                <th>Delivered</th>
                                <th>Sent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-0">Your order is ready!</h6>
                                        <small class="text-muted">Order pickup notification</small>
                                    </div>
                                </td>
                                <td>45</td>
                                <td><span class="badge bg-success">98%</span></td>
                                <td>2 hours ago</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-0">50% off weekend special</h6>
                                        <small class="text-muted">Promotional campaign</small>
                                    </div>
                                </td>
                                <td>1,234</td>
                                <td><span class="badge bg-success">95%</span></td>
                                <td>1 day ago</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function btnStart(btn, text){ 
    if(!btn) return; 
    btn.disabled = true; 
    btn.dataset._orig = btn.innerHTML; 
    btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;vertical-align:-2px;"></span> ' + (text||'Processing...'); 
}

function btnDone(btn){ 
    if(!btn) return; 
    btn.disabled = false; 
    if(btn.dataset._orig){ 
        btn.innerHTML = btn.dataset._orig; 
        delete btn.dataset._orig; 
    } 
}

document.querySelector('form[action="{{ route('notifications.send') }}"]').addEventListener('submit', function(){ 
    const b=document.getElementById('sendBtn'); 
    btnStart(b,'Sending...'); 
    setTimeout(()=>btnDone(b), 1200); 
});

function updateCount(id, outId, max){
    const v = document.getElementById(id).value.length; 
    document.getElementById(outId).textContent = v + ' / ' + max;
}

document.getElementById('title').addEventListener('input', ()=>updateCount('title','titleCount',120));
document.getElementById('body').addEventListener('input', ()=>updateCount('body','bodyCount',500));
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('body').addEventListener('input', updatePreview);

updateCount('title','titleCount',120); 
updateCount('body','bodyCount',500);

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

function updatePreview() {
    const title = document.getElementById('title').value || 'Notification Title';
    const body = document.getElementById('body').value || 'Your notification message will appear here...';
    
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewBody').textContent = body;
}

function loadTemplate(type) {
    const templates = {
        order_ready: {
            title: 'Your order is ready for pickup! 🎉',
            body: 'Hi there! Your delicious order is hot and ready for pickup. Please come by within 10 minutes to ensure freshness.'
        },
        promotion: {
            title: '🍕 50% OFF Everything - Limited Time!',
            body: 'Don\'t miss out! Get 50% off your entire order today only. Use code SAVE50 at checkout. Valid until midnight!'
        },
        reminder: {
            title: 'Missing our delicious food? 😋',
            body: 'It\'s been a while since your last order! Come back and treat yourself to your favorites with 15% off your next order.'
        },
        welcome: {
            title: 'Welcome to our food family! 🎉',
            body: 'Thanks for joining us! Enjoy 20% off your first order with code WELCOME20. We can\'t wait to serve you something amazing!'
        }
    };
    
    if (templates[type]) {
        document.getElementById('title').value = templates[type].title;
        document.getElementById('body').value = templates[type].body;
        updateCount('title','titleCount',120);
        updateCount('body','bodyCount',500);
        updatePreview();
    }
}

function previewNotification() {
    const title = document.getElementById('title').value;
    const body = document.getElementById('body').value;
    
    if (!title || !body) {
        alert('Please enter both title and body for the notification.');
        return;
    }
    
    // Scroll to preview
    document.querySelector('.notification-preview').scrollIntoView({ behavior: 'smooth' });
    
    // Add flash effect
    const preview = document.querySelector('.notification-preview');
    preview.style.animation = 'pulse 0.5s ease-in-out';
    setTimeout(() => {
        preview.style.animation = '';
    }, 500);
}

function saveDraft() {
    const title = document.getElementById('title').value;
    const body = document.getElementById('body').value;
    
    if (!title && !body) {
        alert('Please enter some content to save as draft.');
        return;
    }
    
    // Save to localStorage
    const draft = {
        title: title,
        body: body,
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('notification_draft', JSON.stringify(draft));
    
    // Show success message
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check me-2"></i>Saved!';
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

function loadTemplates() {
    // This could load templates from server
    alert('Template manager coming soon!');
}

function scanQR() {
    alert('QR Scanner integration coming soon!');
}

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('notification_draft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            if (confirm('Load saved draft from ' + new Date(data.timestamp).toLocaleString() + '?')) {
                document.getElementById('title').value = data.title || '';
                document.getElementById('body').value = data.body || '';
                updateCount('title','titleCount',120);
                updateCount('body','bodyCount',500);
                updatePreview();
            }
        } catch (e) {
            console.log('Error loading draft:', e);
        }
    }
    
    updatePreview();
});
</script>
@endsection
