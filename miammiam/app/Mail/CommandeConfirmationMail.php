<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * La commande pour l'email
     */
    public $commande;

    /**
     * Create a new message instance.
     */
    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $numero = $this->commande->numero_commande ?? 'N/A';
        return new Envelope(
            subject: 'Confirmation de votre commande #' . $numero,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

    public function attachments(): array
    {
        return [];
    }
}
