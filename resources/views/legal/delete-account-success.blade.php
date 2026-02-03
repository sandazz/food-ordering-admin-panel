<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted - Moi ! Poke</title>
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
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 20px;
        }
        
        p {
            margin-bottom: 15px;
            color: #555;
            font-size: 1.1em;
        }
        
        .success-box {
            background-color: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .success-box p {
            color: #155724;
            font-weight: 600;
            margin: 0;
        }
        
        .info-text {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-text p {
            color: #1565c0;
            font-size: 0.95em;
            margin-bottom: 10px;
        }
        
        .info-text p:last-child {
            margin-bottom: 0;
        }
        
        .feedback-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .feedback-box h3 {
            color: #495057;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        
        .feedback-box p {
            color: #6c757d;
            font-size: 0.95em;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅</div>
        <h1>Account Successfully Deleted</h1>
        
        <div class="success-box">
            <p>Your account ({{ $email }}) has been permanently deleted</p>
        </div>
        
        <p>All your personal data and information associated with this account have been removed from our systems.</p>
        
        <div class="info-text">
            <p><strong>What has been deleted:</strong></p>
            <p>• Your account credentials and login information</p>
            <p>• All personal information and profile data</p>
            <p>• Order history and transaction records</p>
            <p>• Saved preferences and settings</p>
            <p>• Device tokens and notification settings</p>
        </div>
        
        <div class="feedback-box">
            <h3>We're sorry to see you go</h3>
            <p>Thank you for using Moi ! Poke. If you have any feedback about your experience or reasons for leaving, we'd love to hear from you to help us improve our service.</p>
        </div>
        
        <p style="font-size: 0.9em; color: #6c757d; margin-top: 30px;">
            If you change your mind, you can always create a new account to use our services again.
        </p>
    </div>
</body>
</html>
