<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Approved</title>
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
        .success {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .business-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Business Approved!</h1>
            <p style="margin: 5px 0 0; opacity: 0.9;">Your business is now live on Omniscient</p>
        </div>
        
        <div class="content">
            <h2>Congratulations {{ $owner->name }}!</h2>
            
            <div class="success">
                <p style="margin: 0;">Your business <strong>"{{ $business->name }}"</strong> has been approved and is now published on Omniscient.</p>
            </div>
            
            <div class="business-details">
                <p><strong>Business Name:</strong> {{ $business->name }}</p>
                <p><strong>Category:</strong> {{ $business->categories->pluck('name')->join(', ') }}</p>
                <p><strong>Status:</strong> Published</p>
            </div>
            
            <p>Your business is now visible to thousands of potential customers. Here's what you can do next:</p>
            <ul>
                <li>📊 Check your business analytics</li>
                <li>📱 Share your business on social media</li>
                <li>💬 Respond to customer inquiries</li>
                <li>📈 Monitor your listing performance</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ $viewUrl }}" class="btn">View Your Business</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Thank you for choosing Omniscient. We're excited to help your business grow!
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Omniscient. All rights reserved.</p>
        </div>
    </div>
</body>
</html>