<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $otp,
        public readonly int    $expiryMinutes = 10,
        public readonly string $verifyUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Mweela Cash Capital Login Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.login-otp');
    }
}
