<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Le paiement pour le reçu
     */
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment->load(['commande.articles.menuItem', 'commande.utilisateur']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $numero = $this->payment->commande->numero_commande ?? 'N/A';
        return new Envelope(
            subject: 'Reçu de paiement - Commande #' . $numero,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Vérifier si DomPDF est installé
        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            Log::error('DomPDF non installé. Installez avec: composer require barryvdh/laravel-dompdf');
            return [];
        }

        try {
            // Vérifier que le logo existe
            $logoPath = public_path('logo.jpg');
            if (!file_exists($logoPath)) {
                // Essayer depuis la racine du projet
                $logoPath = base_path('logo.jpg');
            }
            
            // Générer le PDF du reçu
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', [
                'payment' => $this->payment,
                'commande' => $this->payment->commande,
                'logoPath' => $logoPath
            ]);

            $pdfFileName = 'recu_' . $this->payment->commande->numero_commande . '_' . date('YmdHis') . '.pdf';

            return [
                Attachment::fromData(fn () => $pdf->output(), $pdfFileName)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF', [
                'error' => $e->getMessage(),
                'payment_id' => $this->payment->id_payment
            ]);
            return [];
        }
    }
}


namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Le paiement pour le reçu
     */
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment->load(['commande.articles.menuItem', 'commande.utilisateur']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $numero = $this->payment->commande->numero_commande ?? 'N/A';
        return new Envelope(
            subject: 'Reçu de paiement - Commande #' . $numero,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Vérifier si DomPDF est installé
        if (!class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            Log::error('DomPDF non installé. Installez avec: composer require barryvdh/laravel-dompdf');
            return [];
        }

        try {
            // Vérifier que le logo existe
            $logoPath = public_path('logo.jpg');
            if (!file_exists($logoPath)) {
                // Essayer depuis la racine du projet
                $logoPath = base_path('logo.jpg');
            }
            
            // Générer le PDF du reçu
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', [
                'payment' => $this->payment,
                'commande' => $this->payment->commande,
                'logoPath' => $logoPath
            ]);

            $pdfFileName = 'recu_' . $this->payment->commande->numero_commande . '_' . date('YmdHis') . '.pdf';

            return [
                Attachment::fromData(fn () => $pdf->output(), $pdfFileName)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF', [
                'error' => $e->getMessage(),
                'payment_id' => $this->payment->id_payment
            ]);
            return [];
        }
    }
}

