<!-- resources/views/emails/notification.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0284c7; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; padding: 12px 24px; background: #0284c7; color: white; text-decoration: none; border-radius: 6px; margin-top: 16px; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">{{ $title }}</h1>
        </div>
        <div class="content">
            <p>{{ $message }}</p>
            @if($actionUrl)
                <a href="{{ $actionUrl }}" class="button">View Details</a>
            @endif
            <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">
                You received this email because you subscribed to notifications.
                <br>
                <a href="{{ route('profile.notifications') }}" style="color: #0284c7;">Manage preferences</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Omniscient Directory. All rights reserved.</p>
        </div>
    </div>
</body>
</html>