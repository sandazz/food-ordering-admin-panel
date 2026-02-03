<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Email Sent - Moi ! Poke</title>
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
        
        .email-box {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .email-box p {
            color: #2e7d32;
            font-weight: 600;
            margin: 0;
        }
        
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .info-box p {
            color: #1565c0;
            font-size: 0.95em;
            margin-bottom: 10px;
        }
        
        .info-box p:last-child {
            margin-bottom: 0;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            background-color: #667eea;
            color: white;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
        <div class="icon">📧</div>
        <h1>Check Your Email</h1>
        
        <p>We've sent a verification email to:</p>
        
        <div class="email-box">
            <p>{{ $email }}</p>
        </div>
        
        <p>Please check your inbox and click the verification link to confirm your account deletion request.</p>
        
        <div class="info-box">
            <p><strong>Important:</strong></p>
            <p>• The verification link will expire in 1 hour</p>
            <p>• Check your spam/junk folder if you don't see the email</p>
            <p>• Your account will only be deleted after you click the verification link</p>
        </div>
        
        <a href="{{ route('privacy.policy') }}" class="btn">Back to Privacy Policy</a>
    </div>
</body>
</html>
