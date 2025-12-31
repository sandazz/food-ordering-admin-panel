@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title">
                <i class="bi bi-bell me-2"></i>{{ \App\Utils\UIStrings::t('notifications.title') }}
            </h1>
            <p class="page-subtitle text-muted">Send push notifications to customers</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle me-2"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-send me-2"></i>Compose & Send Notification
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('notifications.store') }}" id="notificationForm">
                        @csrf
                        
                        <!-- Target Restaurant Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-building me-2"></i>Target Restaurant
                            </label>
                            @if($role === 'admin')
                            <select name="target_restaurant" id="target_restaurant" class="form-select" required>
                                <option value="all">All Restaurants (All Customers)</option>
                                @foreach($restaurants as $restaurant)
                                <option value="{{ $restaurant['id'] }}">{{ $restaurant['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Select a specific restaurant or send to all customers
                            </div>
                            @else
                            <input type="hidden" name="target_restaurant" value="{{ $restaurants[0]['id'] ?? '' }}">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Notifications will be sent to customers of: <strong>{{ $restaurants[0]['name'] ?? 'Your Restaurant' }}</strong>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <!-- Notification Content -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-chat-text me-2"></i>Notification Content
                            </label>
                            
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-card-heading"></i>
                                    </span>
                                    <input type="text" name="title" id="title" class="form-control" 
                                           required maxlength="120" placeholder="Enter notification title..." 
                                           value="{{ old('title') }}">
                                    <span class="input-group-text">
                                        <small id="titleCount" class="text-muted">0/120</small>
                                    </span>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    You can write in English or Finnish
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <div class="position-relative">
                                    <textarea name="body" id="body" class="form-control" required maxlength="500" 
                                              rows="5" placeholder="Write your notification message...">{{ old('body') }}</textarea>
                                    <span class="position-absolute bottom-0 end-0 m-2">
                                        <small id="bodyCount" class="text-muted">0/500</small>
                                    </span>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    You can write in English or Finnish
                                </div>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="card bg-light mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-eye me-2"></i>Notification Preview
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="notification-preview p-3 bg-white border rounded shadow-sm" style="max-width: 400px;">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-bell-fill text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold" id="previewTitle">Your Notification Title</h6>
                                            <p class="mb-0 text-muted small" id="previewBody">Your notification message will appear here...</p>
                                            <small class="text-muted">Just now</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg" id="sendBtn">
                                <i class="bi bi-send me-2"></i>Send Notification Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Section (Optional) -->
    <!-- <div class="row g-4 mt-2">
        <div class="col-lg-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-bug me-2"></i>Test Notification (Development Only)
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Use this section to send a test notification to a specific FCM token for testing purposes.
                    </p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="testToken" placeholder="Enter FCM token for testing...">
                        <button class="btn btn-warning" type="button" onclick="sendTestNotification()">
                            <i class="bi bi-send me-2"></i>Send Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
</div>

<style>
.notification-preview {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

#notificationForm .was-validated .form-control:invalid {
    border-color: #dc3545;
}

#notificationForm .was-validated .form-control:valid {
    border-color: #198754;
}

.nav-tabs .nav-link {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 600;
}
</style>

<script>
// Character counters
function updateCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    
    input.addEventListener('input', function() {
        const length = this.value.length;
        counter.textContent = `${length}/${maxLength}`;
        
        if (length > maxLength * 0.9) {
            counter.classList.add('text-danger');
        } else if (length > maxLength * 0.7) {
            counter.classList.add('text-warning');
            counter.classList.remove('text-danger');
        } else {
            counter.classList.remove('text-warning', 'text-danger');
        }
    });
}

// Initialize counters
updateCounter('title', 'titleCount', 120);
updateCounter('body', 'bodyCount', 500);

// Live preview update
document.getElementById('title').addEventListener('input', function() {
    const previewTitle = document.getElementById('previewTitle');
    previewTitle.textContent = this.value || 'Your Notification Title';
});

document.getElementById('body').addEventListener('input', function() {
    const previewBody = document.getElementById('previewBody');
    previewBody.textContent = this.value || 'Your notification message will appear here...';
});

// Form submission with loading state
document.getElementById('notificationForm').addEventListener('submit', function(e) {
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
});

// Reset form
function resetForm() {
    document.getElementById('notificationForm').reset();
    document.getElementById('previewTitle').textContent = 'Your Notification Title';
    document.getElementById('previewBody').textContent = 'Your notification message will appear here...';
    document.getElementById('titleCount').textContent = '0/120';
    document.getElementById('bodyCount').textContent = '0/500';
}

// Test notification (with AJAX)
function sendTestNotification() {
    const token = document.getElementById('testToken').value.trim();
    if (!token) {
        alert('Please enter a valid FCM token');
        return;
    }
    
    if (token.length < 20) {
        alert('FCM token seems too short. Please check and try again.');
        return;
    }
    
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    
    fetch('{{ route('notifications.test') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ token: token })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        
        // Check if response is JSON
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            if (response.ok && data.success) {
                alert('✅ ' + data.message);
                console.log('Test result:', data.result);
            } else {
                alert('❌ ' + (data.message || 'Failed to send test notification'));
                console.error('Test failed:', data);
            }
        } else {
            // Response is HTML (likely an error page)
            const text = await response.text();
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            console.error('Server returned HTML instead of JSON:', text);
            alert('❌ Server error occurred. Check browser console for details.');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('❌ Network error: ' + error.message);
        console.error('Test notification error:', error);
    });
}

// Initialize character counts on page load
document.addEventListener('DOMContentLoaded', function() {
    ['title', 'body'].forEach(id => {
        const input = document.getElementById(id);
        if (input && input.value) {
            input.dispatchEvent(new Event('input'));
        }
    });
});
</script>
@endsection
