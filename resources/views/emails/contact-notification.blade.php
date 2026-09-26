<!-- resources/views/emails/contact-notification.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Contact Message</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <div style="background: #0284c7; padding: 20px; border-radius: 8px 8px 0 0; color: white; text-align: center;">
            <h1 style="margin: 0;">New Contact Message</h1>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 0 0 8px 8px;">
            <p><strong>From:</strong> {{ $contact->name }}</p>
            <p><strong>Email:</strong> <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></p>
            @if($contact->phone)
                <p><strong>Phone:</strong> {{ $contact->phone }}</p>
            @endif
            <p><strong>Subject:</strong> {{ $contact->subject }}</p>
            
            <hr style="border: 1px solid #eee; margin: 20px 0;">
            
            <h3>Message:</h3>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 4px; white-space: pre-wrap;">{{ $contact->message }}</p>
            
            <hr style="border: 1px solid #eee; margin: 20px 0;">
            
            <p style="font-size: 14px; color: #666;">
                <a href="{{ route('admin.contacts.show', $contact->id) }}" 
                   style="display: inline-block; background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                    View in Admin Panel
                </a>
            </p>
            
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                This message was sent from the Omniscient contact form.
            </p>
        </div>
    </div>
</body>
</html>