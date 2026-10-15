<?php

namespace App\Mail;

use App\Models\DeveloperConcern;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeveloperConcernAcknowledgedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DeveloperConcern $concern,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Developer Concern #{$this->concern->reference} Acknowledged",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.developer-concern-acknowledged',
            with: [
                'concern' => $this->concern,
            ],
        );
    }
}
