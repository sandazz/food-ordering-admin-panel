<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Your Account - Moi ! Poke</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background-color: #fff;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        
        h1 {
            color: #e74c3c;
            font-size: 2em;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .warning-box h2 {
            color: #856404;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        .warning-box ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .warning-box li {
            margin-bottom: 8px;
        }
        
        p {
            margin-bottom: 15px;
            text-align: center;
            color: #555;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            margin-top: 15px;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        /* Custom Confirmation Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .modal-title {
            color: #e74c3c;
            font-size: 1.8em;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .modal-message {
            color: #555;
            font-size: 1.1em;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-warning {
            background-color: #fff3cd;
            border-left: 4button" id="deleteBtn" class="btn btn-danger">
                Delete My Account Permanently
            </button>
        </form>
        
        <a href="{{ route('privacy.policy') }}" class="btn btn-secondary">Cancel</a>
        
        <div class="back-link">
            <a href="{{ route('privacy.policy') }}">← Back to Privacy Policy</a>
        </div>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">Final Confirmation</h2>
            <p class="modal-message">Are you absolutely sure you want to delete your account?</p>
            
            <div class="modal-warning">
                <p><strong>⚡ This will happen immediately:</strong></p>
                <p>• Your account will be permanently deleted</p>
                <p>• All your data will be erased</p>
                <p>• This action CANNOT be undone</p>
            </div>
            
            <div class="modal-buttons">
                <button type="button" id="cancelBtn" class="modal-btn modal-btn-cancel">
                    Cancel
                </button>
                <button type="button" id="confirmBtn" class="modal-btn modal-btn-confirm">
                    Yes, Delete Forever
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const deleteBtn = document.getElementById('deleteBtn');
        const confirmModal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const form = document.querySelector('form');
        
        // Show modal when delete button is clicked
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate email first
            const emailInput = document.getElementById('email');
            if (!emailInput.value || !emailInput.validity.valid) {
                emailInput.focus();
                emailInput.reportValidity();
                return;
            }
            
            confirmModal.classList.add('active');
        });
        
        // Hide modal when cancel is clicked
        cancelBtn.addEventListener('click', function() {
            confirmModal.classList.remove('active');
        });
        
        // Close modal when clicking outside
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                confirmModal.classList.remove('active');
            }
        });
        
        // Submit form when confirm is clicked
        confirmBtn.addEventListener('click', function() {
            form.submit();
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmModal.classList.contains('active')) {
                confirmModal.classList.remove('active');
            }
        });
    </script   margin: 0;
            text-align: left;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 140px;
        }
        
        .modal-btn-confirm {
            background-color: #e74c3c;
            color: white;
        }
        
        .modal-btn-confirm:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        
        .modal-btn-cancel {
            background-color: #95a5a6;
            color: white;
        }
        
        .modal-btn-cancel:hover {
            background-color: #7f8c8d;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }
            
            h1 {
                font-size: 1.5em;
            }
            
            .modal-content {
                padding: 30px 20px;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
            
            .modal-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Delete Your Account</h1>
        
        <p>We're sorry to see you go. Before proceeding, please understand the consequences of deleting your account.</p>
        
        <div class="warning-box">
            <h2>⚠️ This action is permanent and cannot be undone</h2>
            <ul>
                <li>Your account will be permanently deleted immediately</li>
                <li>All your personal information will be removed</li>
                <li>Your order history will be deleted</li>
                <li>You will lose access to any loyalty points or rewards</li>
                <li>You will need to create a new account if you want to use our service again</li>
            </ul>
        </div>
        
        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif
        
        <form action="{{ route('account.delete.request') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">Enter your email address to proceed:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="your.email@example.com" 
                    required 
                    value="{{ old('email') }}"
                >
                @error('email')
                    <span style="color: #e74c3c; font-size: 14px;">{{ $message }}</span>
                @enderror
            </div>
            
            <button type="button" id="deleteBtn" class="btn btn-danger">
                Delete My Account Permanently
            </button>
        </form>
        
        <a href="{{ route('privacy.policy') }}" class="btn btn-secondary">Cancel</a>
        
        <div class="back-link">
            <a href="{{ route('privacy.policy') }}">← Back to Privacy Policy</a>
        </div>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">Final Confirmation</h2>
            <p class="modal-message">Are you absolutely sure you want to delete your account?</p>
            
            <div class="modal-warning">
                <p><strong>⚡ This will happen immediately:</strong></p>
                <p>• Your account will be permanently deleted</p>
                <p>• All your data will be erased</p>
                <p>• This action CANNOT be undone</p>
            </div>
            
            <div class="modal-buttons">
                <button type="button" id="cancelBtn" class="modal-btn modal-btn-cancel">
                    Cancel
                </button>
                <button type="button" id="confirmBtn" class="modal-btn modal-btn-confirm">
                    Yes, Delete Forever
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const deleteBtn = document.getElementById('deleteBtn');
        const confirmModal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const form = document.querySelector('form');
        
        // Show modal when delete button is clicked
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validate email first
            const emailInput = document.getElementById('email');
            if (!emailInput.value || !emailInput.validity.valid) {
                emailInput.focus();
                emailInput.reportValidity();
                return;
            }
            
            confirmModal.classList.add('active');
        });
        
        // Hide modal when cancel is clicked
        cancelBtn.addEventListener('click', function() {
            confirmModal.classList.remove('active');
        });
        
        // Close modal when clicking outside
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                confirmModal.classList.remove('active');
            }
        });
        
        // Submit form when confirm is clicked
        confirmBtn.addEventListener('click', function() {
            form.submit();
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmModal.classList.contains('active')) {
                confirmModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>
