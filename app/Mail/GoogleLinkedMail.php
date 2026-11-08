<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the owner of an existing account when "Continue with Google" has
 * just been linked to it, so an unwanted link doesn't go unnoticed.
 */
class GoogleLinkedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        $fromAddress = (string) (config('mail.from.address') ?: 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, 'Mally Store'),
            subject: __('Google sign-in was added to your Mally account'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.google-linked');
    }
}
