<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $trialEndsAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your free trial ends in 3 days',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-ending',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
