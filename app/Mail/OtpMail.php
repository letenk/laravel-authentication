<?php

namespace App\Mail;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Otp $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Verification Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp',
            with: [
                'name'             => $this->user->name,
                'code'             => $this->otp->code,
                'expiresInMinutes' => 10,
            ],
        );
    }
}
