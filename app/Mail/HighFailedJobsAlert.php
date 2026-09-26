<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HighFailedJobsAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param int   $totalFailed   Total failed jobs in the window
     * @param array $recentJobs    Array of ['id','queue','failed_at','exception_summary']
     * @param int   $windowMinutes The detection window in minutes
     * @param int   $threshold     The trigger threshold
     */
    public function __construct(
        public int $totalFailed,
        public array $recentJobs,
        public int $windowMinutes,
        public int $threshold,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '⚠️ Alert: %d failed queue jobs in the last %d minutes',
                $this->totalFailed,
                $this->windowMinutes,
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.high-failed-jobs',
        );
    }
}