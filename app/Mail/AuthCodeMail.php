<?php

namespace App\Mail;

use App\Models\AuthCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SensitiveParameter;

class AuthCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public string $purpose = AuthCode::PURPOSE_REGISTER,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) (config('mail.from.address') ?: 'hello@example.com');

        return new Envelope(
            from: new Address($fromAddress, 'Mally Store'),
            subject: 'Код подтверждения Mally Store',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth-code',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
