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
    max-width: 400px;
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
}

.auth-body {
    padding: 2rem;
}

.form-floating {
    margin-bottom: 1.5rem;
}

.form-floating > .form-control {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem 1rem;
    height: 58px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-floating > .form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-floating > label {
    color: #6b7280;
    font-weight: 500;
    padding: 1rem 1rem;
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
}

.auth-footer a:hover {
    color: #4f46e5;
}

.password-toggle {
    cursor: pointer;
    color: #6b7280;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #6366f1;
}

.alert {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
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
}
</style>

<div class="auth-container">
    <div class="floating-shapes">
        <div class="shape">
            <i class="bi bi-hexagon-fill" style="font-size: 2rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-circle-fill" style="font-size: 1.5rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-triangle-fill" style="font-size: 1.8rem; color: white;"></i>
        </div>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="bi bi-shop"></i>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your admin dashboard</p>
        </div>

        <div class="auth-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('status'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" id="loginForm">
                @csrf

                <div class="input-group">
                    <input id="email" type="email" class="form-control" name="email" 
                           value="{{ old('email') }}" required autofocus placeholder="Enter your email address">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                </div>

                <div class="input-group">
                    <input id="password" type="password" class="form-control" name="password" 
                           required placeholder="Enter your password">
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label text-muted" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-auth" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In to Dashboard
                </button>
            </form>
        </div>

        <div class="auth-footer">
            <p class="text-muted mb-0">
                Don't have an account? 
                <a href="#" class="ms-1">Contact Administrator</a>
            </p>
        </div>
    </div>
    <div class="mt-3">
        <a href="{{ route('password.request') }}">Forgot your password?</a>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
    }
}

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

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btnStart(btn, 'Signing In...');
    
    // Reset button after timeout if needed
    setTimeout(() => btnDone(btn), 5000);
});

// Add floating animation to shapes
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
});
</script>
@endsection
