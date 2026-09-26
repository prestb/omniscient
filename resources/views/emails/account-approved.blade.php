<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #16a34a;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .btn {
            display: inline-block;
            background: #0284c7;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 15px;
        }
        .btn:hover {
            background: #0369a1;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .success {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Account Approved!</h1>
            <p style="margin: 5px 0 0; opacity: 0.9;">Welcome to Omniscient</p>
        </div>
        
        <div class="content">
            <h2>Welcome, {{ $user->name }}!</h2>
            
            <div class="success">
                <p style="margin: 0;">Your account has been approved. You can now log in and start managing your business profile.</p>
            </div>
            
            <p>Here's what you can do now:</p>
            <ul>
                <li>✅ Complete your business profile</li>
                <li>✅ Add your business branches</li>
                <li>✅ Set your operating hours</li>
                <li>✅ Upload business images</li>
                <li>✅ Submit your profile for publication</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="btn">Log In to Your Account</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                If you have any questions, please contact our support team.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Omniscient. All rights reserved.</p>
        </div>
    </div>
</body>
</html>