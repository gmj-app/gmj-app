<?php

namespace App\Mail;

use App\Models\BetaFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BetaFeedbackSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly BetaFeedback $feedback,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Guide My Journey testing feedback',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.beta-feedback-submitted',
        );
    }
}
