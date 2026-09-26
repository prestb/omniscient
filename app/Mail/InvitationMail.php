<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve Been Invited to Join the Omniscient Platform',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->invitation->invitation_link,
                'expiresAt' => $this->invitation->expires_at->format('F j, Y'),
            ]
        );
    }
}