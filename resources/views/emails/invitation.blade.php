<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation to Omniscient</title>
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
            background: #0284c7;
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
        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .warning {
            color: #dc2626;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 You're Invited!</h1>
            <p style="margin: 5px 0 0; opacity: 0.9;">Join Omniscient</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $invitation->name }}!</h2>
            
            <p>We're excited to invite you to join the <strong>Omniscient Platform</strong> – the leading business directory in Cameroon.</p>
            
            <div class="details">
                <p><strong>Business:</strong> {{ $invitation->business_name ?? 'Not specified' }}</p>
                <p><strong>Email:</strong> {{ $invitation->email }}</p>
                <p><strong>Expires:</strong> {{ $expiresAt }}</p>
            </div>
            
            <p>To get started, simply click the button below to create your account and list your business.</p>
            
            <div style="text-align: center;">
                <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                <strong>Note:</strong> This invitation is valid for one-time use and will expire on {{ $expiresAt }}.
            </p>
            
            <p class="warning">
                ⚠️ If you did not request this invitation, please ignore this email.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Omniscient. All rights reserved.</p>
            <p style="margin: 5px 0 0;">
                <small>This is an automated message, please do not reply to this email.</small>
            </p>
        </div>
    </div>
</body>
</html>