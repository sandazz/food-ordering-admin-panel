@extends('layouts.app')

@section('content')
<style>
.auth-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
    width: 100%;
    max-width: 420px;
}

.auth-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 2.5rem 2rem 2rem;
    text-align: center;
    position: relative;
}

.auth-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.15"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.15"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.auth-logo {
    background: rgba(255, 255, 255, 0.2);
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    position: relative;
    z-index: 1;
}

.auth-logo i {
    font-size: 2rem;
    color: white;
}

.auth-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    position: relative;
    z-index: 1;
}

.auth-subtitle {
    opacity: 0.9;
    margin: 0;
    font-size: 0.95rem;
    position: relative;
    z-index: 1;
    line-height: 1.5;
}

.auth-body {
    padding: 2rem;
}

.info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.info-card i {
    color: #0284c7;
    font-size: 1.1rem;
    margin-top: 0.1rem;
}

.info-card-content {
    flex: 1;
}

.info-card-title {
    font-weight: 600;
    color: #0c4a6e;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.info-card-text {
    color: #075985;
    font-size: 0.85rem;
    line-height: 1.4;
    margin: 0;
}

.input-group {
    margin-bottom: 1.5rem;
}

.input-group .form-control {
    border: 2px solid #e5e7eb;
    border-radius: 12px 0 0 12px;
    padding: 0.875rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    height: 50px;
}

.input-group .form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    z-index: 3;
}

.input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-left: none;
    border-radius: 0 12px 12px 0;
    color: #6b7280;
    padding: 0.875rem 1rem;
    height: 50px;
    display: flex;
    align-items: center;
}

.btn-auth {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    padding: 0.875rem 2rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    color: white;
}

.btn-auth:active {
    transform: translateY(0);
}

.btn-auth::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn-auth:hover::before {
    left: 100%;
}

.auth-footer {
    text-align: center;
    padding: 1.5rem 2rem 2rem;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}

.auth-footer a {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.auth-footer a:hover {
    color: #4f46e5;
}

.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}

.shape {
    position: absolute;
    opacity: 0.1;
    animation: float 6s ease-in-out infinite;
}

.shape:nth-child(1) {
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.shape:nth-child(2) {
    top: 20%;
    right: 10%;
    animation-delay: 2s;
}

.shape:nth-child(3) {
    bottom: 10%;
    left: 20%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

@media (max-width: 768px) {
    .auth-container {
        padding: 1rem;
    }
    
    .auth-card {
        border-radius: 16px;
        max-width: 100%;
    }
    
    .auth-header {
        padding: 2rem 1.5rem 1.5rem;
    }
    
    .auth-body {
        padding: 1.5rem;
    }
    
    .auth-footer {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .auth-title {
        font-size: 1.5rem;
    }
    
    .input-group .form-control,
    .input-group-text,
    .btn-auth {
        height: 48px;
    }
    
    .info-card {
        padding: 0.875rem;
    }
}
</style>

<div class="auth-container">
    <div class="floating-shapes">
        <div class="shape">
            <i class="bi bi-envelope-fill" style="font-size: 2rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-question-circle-fill" style="font-size: 1.5rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-arrow-repeat" style="font-size: 1.8rem; color: white;"></i>
        </div>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="bi bi-envelope"></i>
            </div>
            <h1 class="auth-title">Forgot Password?</h1>
            <p class="auth-subtitle">No worries! Enter your email address and we'll send you a reset link to get you back into your account.</p>
        </div>

        <div class="auth-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('status'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="info-card">
                <i class="bi bi-info-circle"></i>
                <div class="info-card-content">
                    <div class="info-card-title">How this works</div>
                    <p class="info-card-text">
                        We'll send a secure password reset link to your email address. Click the link to create a new password and regain access to your admin dashboard.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
                @csrf

                <div class="input-group">
                    <input id="email" type="email" class="form-control" name="email" 
                           value="{{ old('email') }}" required autofocus placeholder="Enter your email address">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                </div>

                <button type="submit" class="btn btn-auth" id="sendBtn">
                    <i class="bi bi-send me-2"></i>
                    Send Reset Link
                </button>
            </form>

            <!-- <div class="mt-4 p-3" style="background: #f8fafc; border-radius: 10px; border-left: 4px solid #6366f1;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-lightbulb text-warning"></i>
                    <span class="fw-semibold text-muted">Tips</span>
                </div>
                <div class="small text-muted">
                    • Check your spam/junk folder if you don't see the email<br>
                    • The reset link expires in 1 hour for security<br>
                    • Contact support if you need additional help
                </div>
            </div> -->
        </div>

        <div class="auth-footer">
            <p class="text-muted mb-3">
                <small>Remember your password?</small>
            </p>
            <a href="{{ route('login') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left"></i>
                Back to Sign In
            </a>
        </div>
    </div>
</div>

<script>
function btnStart(btn, text) {
    if (!btn) return;
    btn.disabled = true;
    btn.dataset._orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (text || 'Processing...');
}

function btnDone(btn) {
    if (!btn) return;
    btn.disabled = false;
    if (btn.dataset._orig) {
        btn.innerHTML = btn.dataset._orig;
        delete btn.dataset._orig;
    }
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

document.getElementById('forgotForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const btn = document.getElementById('sendBtn');
    
    if (!email) {
        e.preventDefault();
        alert('Please enter your email address.');
        return;
    }
    
    if (!validateEmail(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return;
    }
    
    btnStart(btn, 'Sending Reset Link...');
    
    // Reset button after timeout if needed
    setTimeout(() => btnDone(btn), 8000);
});

// Add floating animation to shapes and focus effects
document.addEventListener('DOMContentLoaded', function() {
    const shapes = document.querySelectorAll('.shape');
    shapes.forEach((shape, index) => {
        shape.style.animationDelay = `${index * 2}s`;
    });
    
    // Add focus effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.input-group').style.transform = 'translateY(-2px)';
            this.closest('.input-group').style.boxShadow = '0 10px 25px rgba(99, 102, 241, 0.1)';
        });
        
        input.addEventListener('blur', function() {
            this.closest('.input-group').style.transform = 'translateY(0)';
            this.closest('.input-group').style.boxShadow = 'none';
        });
    });
    
    // Email input real-time validation
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        if (email && !validateEmail(email)) {
            this.style.borderColor = '#ef4444';
        } else if (email) {
            this.style.borderColor = '#10b981';
        } else {
            this.style.borderColor = '#e5e7eb';
        }
    });
});
</script>
@endsection
