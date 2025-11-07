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
}

.auth-body {
    padding: 2rem;
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

.password-strength {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #e5e7eb;
    font-size: 0.85rem;
}

.password-strength.weak {
    border-left-color: #ef4444;
    background: #fef2f2;
    color: #dc2626;
}

.password-strength.medium {
    border-left-color: #f59e0b;
    background: #fffbeb;
    color: #d97706;
}

.password-strength.strong {
    border-left-color: #10b981;
    background: #f0fdf4;
    color: #059669;
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
}
</style>

<div class="auth-container">
    <div class="floating-shapes">
        <div class="shape">
            <i class="bi bi-shield-lock-fill" style="font-size: 2rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-key-fill" style="font-size: 1.5rem; color: white;"></i>
        </div>
        <div class="shape">
            <i class="bi bi-arrow-clockwise" style="font-size: 1.8rem; color: white;"></i>
        </div>
    </div>

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-subtitle">Create a new secure password for your account</p>
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

            <form method="POST" action="{{ route('password.update') }}" id="resetForm">
                @csrf
                <input type="hidden" name="oobCode" value="{{ $oobCode }}">

                <div class="input-group">
                    <input id="password" type="password" class="form-control" name="password" 
                           required placeholder="Enter your new password" onkeyup="checkPasswordStrength()">
                    <span class="input-group-text password-toggle" onclick="togglePassword('password', 'passwordIcon')">
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </span>
                </div>

                <div id="passwordStrength" class="password-strength" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i>
                        <span id="strengthText">Password strength: </span>
                    </div>
                    <div class="mt-2 small">
                        <div id="requirements">
                            • At least 8 characters<br>
                            • Mix of uppercase and lowercase letters<br>
                            • At least one number
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" 
                           required placeholder="Confirm your new password" onkeyup="checkPasswordMatch()">
                    <span class="input-group-text password-toggle" onclick="togglePassword('password_confirmation', 'confirmIcon')">
                        <i class="bi bi-eye" id="confirmIcon"></i>
                    </span>
                </div>

                <div id="passwordMatch" class="password-strength" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle"></i>
                        <span>Passwords match!</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-auth" id="resetBtn">
                    <i class="bi bi-shield-check me-2"></i>
                    Reset Password
                </button>
            </form>
        </div>

        <div class="auth-footer">
            <p class="text-muted mb-0">
                <a href="{{ route('login') }}" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i>
                    Back to Sign In
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const passwordIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthDiv = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('strengthText');
    
    if (password.length === 0) {
        strengthDiv.style.display = 'none';
        return;
    }
    
    strengthDiv.style.display = 'block';
    
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z\d]/.test(password)) score++;
    
    strengthDiv.className = 'password-strength';
    
    if (score < 2) {
        strengthDiv.classList.add('weak');
        strengthText.textContent = 'Password strength: Weak';
    } else if (score < 3) {
        strengthDiv.classList.add('medium');
        strengthText.textContent = 'Password strength: Medium';
    } else {
        strengthDiv.classList.add('strong');
        strengthText.textContent = 'Password strength: Strong';
    }
    
    checkPasswordMatch();
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
        matchDiv.style.display = 'none';
        return;
    }
    
    if (password === confirmPassword && password.length > 0) {
        matchDiv.style.display = 'block';
        matchDiv.className = 'password-strength strong';
    } else {
        matchDiv.style.display = 'block';
        matchDiv.className = 'password-strength weak';
        matchDiv.innerHTML = '<div class="d-flex align-items-center gap-2"><i class="bi bi-x-circle"></i><span>Passwords don\'t match</span></div>';
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

document.getElementById('resetForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    const btn = document.getElementById('resetBtn');
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match. Please check and try again.');
        return;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return;
    }
    
    btnStart(btn, 'Resetting Password...');
    
    // Reset button after timeout if needed
    setTimeout(() => btnDone(btn), 5000);
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
});
</script>
@endsection
