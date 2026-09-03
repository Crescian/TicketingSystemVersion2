<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent synchronously (no ShouldQueue) — this is a one-off credential delivery,
// not a bulk notification, so it shouldn't silently wait on a queue worker.
class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Has Been Reset',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.temporary-password',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
