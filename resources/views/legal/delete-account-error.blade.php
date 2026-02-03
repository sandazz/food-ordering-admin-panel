<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Moi ! Poke</title>
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
        
        .error-box {
            background-color: #f8d7da;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .error-box p {
            color: #721c24;
            font-size: 1.1em;
            margin: 0;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px;
            background-color: #667eea;
            color: white;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
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
        <div class="icon">❌</div>
        <h1>Something Went Wrong</h1>
        
        <div class="error-box">
            <p>{{ $message }}</p>
        </div>
        
        <a href="{{ route('account.delete.form') }}" class="btn">Try Again</a>
        <a href="{{ route('privacy.policy') }}" class="btn btn-secondary">Back to Privacy Policy</a>
    </div>
</body>
</html>
