<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Deletion - Moi ! Poke</title>
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
            text-align: center;
        }
        
        .icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #e74c3c;
            font-size: 2em;
            margin-bottom: 20px;
        }
        
        p {
            margin-bottom: 15px;
            color: #555;
            font-size: 1.1em;
        }
        
        .email-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .email-box p {
            color: #856404;
            font-weight: 600;
            margin: 0;
            font-size: 1.2em;
        }
        
        .warning-text {
            background-color: #f8d7da;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .warning-text h3 {
            color: #c0392b;
            margin-bottom: 10px;
        }
        
        .warning-text ul {
            margin-left: 20px;
            color: #721c24;
        }
        
        .warning-text li {
            margin-bottom: 8px;
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
            margin: 10px;
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
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .button-group {
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }
            
            h1 {
                font-size: 1.5em;
            }
            
            .icon {
                font-size: 3em;
            }
            
            .btn {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚠️</div>
        <h1>Final Confirmation Required</h1>
        
        <p>You are about to permanently delete the account for:</p>
        
        <div class="email-box">
            <p>{{ $email }}</p>
        </div>
        
        <div class="warning-text">
            <h3>⛔ Last Warning - This Action is Permanent</h3>
            <ul>
                <li>Your account and all associated data will be <strong>permanently deleted</strong></li>
                <li>This includes your order history, preferences, and any saved information</li>
                <li><strong>This action CANNOT be undone</strong></li>
                <li>You will need to create a new account to use our services again</li>
            </ul>
        </div>
        
        <p style="font-weight: 600; color: #e74c3c;">Are you absolutely sure you want to proceed?</p>
        
        <div class="button-group">
            <form action="{{ route('account.delete.confirm', ['token' => $token]) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('THIS IS YOUR LAST CHANCE! Are you absolutely certain you want to permanently delete your account?')">
                    Yes, Delete My Account Permanently
                </button>
            </form>
            
            <a href="{{ route('privacy.policy') }}" class="btn btn-secondary">
                No, Keep My Account
            </a>
        </div>
    </div>
</body>
</html>
