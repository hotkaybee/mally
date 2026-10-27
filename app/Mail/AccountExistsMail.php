<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent instead of a registration code when the email already has an account,
 * so only the mailbox owner learns that the account exists.
 */
class AccountExistsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        $fromAddress = (string) (config('mail.from.address') ?: 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, 'Mally Store'),
            subject: __('You already have a Mally account'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-exists');
    }
}
