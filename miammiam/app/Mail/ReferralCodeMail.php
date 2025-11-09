<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $referralCode;
    public $userName;
    public $recipientEmail;
    public $frontendUrl;

    public function __construct($referralCode, $userName, $recipientEmail)
    {
        $this->referralCode = $referralCode;
        $this->userName = $userName;
        $this->recipientEmail = $recipientEmail;
        $this->frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Code de parrainage Miam Miam - Gagnez des points ensemble !',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

