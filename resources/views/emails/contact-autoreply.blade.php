<!-- resources/views/emails/contact-autoreply.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thank You for Contacting Us</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="background: #0284c7; padding: 20px; border-radius: 8px 8px 0 0; color: white; text-align: center;">
            <h1 style="margin: 0;">Thank You!</h1>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 0 0 8px 8px;">
            <p>Dear {{ $contact->name }},</p>
            
            <p>Thank you for contacting <strong>Omniscient</strong>. We have received your message and will get back to you as soon as possible.</p>
            
            <div style="background: #f0f7ff; padding: 15px; border-radius: 4px; border-left: 4px solid #0284c7; margin: 20px 0;">
                <h4 style="margin: 0 0 10px 0;">Your Message:</h4>
                <p style="margin: 0; font-size: 14px; color: #555;"><strong>Subject:</strong> {{ $contact->subject }}</p>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #555;">{{ $contact->message }}</p>
            </div>
            
            <p>We typically respond within 24-48 hours.</p>
            
            <hr style="border: 1px solid #eee; margin: 20px 0;">
            
            <p style="font-size: 14px; color: #666;">
                In the meantime, you can explore more businesses on our 
                <a href="{{ route('directory') }}" style="color: #0284c7; text-decoration: none;">Directory</a>.
            </p>
            
            <p style="font-size: 14px; color: #999; margin-top: 20px;">
                Best regards,<br>
                <strong>Omniscient Team</strong>
            </p>
        </div>
    </div>
</body>
</html>