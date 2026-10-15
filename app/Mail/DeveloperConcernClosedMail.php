<?php

namespace App\Mail;

use App\Models\DeveloperConcern;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeveloperConcernClosedMail extends Mailable implements ShouldQueue
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
            subject: "Developer Concern #{$this->concern->reference} Closed",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.developer-concern-closed',
            with: [
                'concern' => $this->concern,
            ],
        );
    }
}
