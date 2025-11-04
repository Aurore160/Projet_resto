<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\InitializePaymentRequest;
use App\Services\EasypayService;
use App\Models\Commande;
use App\Models\Payment;
use App\Models\Notification;
use App\Mail\PaymentReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Mail\Mailer;

class PaymentController extends Controller
{
    use HandlesApiResponses;
    
    protected $easypayService;
    protected $logger;
    protected $mailer;

    public function __construct(
        EasypayService $easypayService,
        LoggerInterface $logger,
        Mailer $mailer
    ) {
        $this->easypayService = $easypayService;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Initialiser un paiement pour une commande
     * 
     * POST /api/payments/initialize
     */
    public function initialize(InitializePaymentRequest $request)
    {
        try {
            $user = $request->user();
            $data = $request->validated();
            
            // Récupérer la commande
            $commande = Commande::with('utilisateur')
                ->where('id_commande', $data['commande_id'])
                ->first();

            if (!$commande) {
                return $this->notFoundResponse('Commande');
            }

            // Vérifier que la commande appartient à l'utilisateur connecté
            if ($commande->id_utilisateur !== $user->id_utilisateur) {
                return $this->unauthorizedResponse('Vous n\'êtes pas autorisé à payer cette commande');
            }

            // Vérifier que la commande n'est pas déjà payée
            $existingPayment = Payment::where('id_commande', $commande->id_commande)
                ->where('statut_payment', 'paye')
                ->first();

            if ($existingPayment) {
                return $this->errorResponse('Cette commande est déjà payée', 400);
            }

            // Calculer le montant total
            $montantTotal = $commande->montant_total ?? $commande->getTotal();

            // Déterminer les channels Easypay et la méthode de paiement selon le choix
            $paymentConfig = $this->preparePaymentChannels($data['payment_method'] ?? null);
            $channels = $paymentConfig['channels'];
            $methodePayment = $paymentConfig['methode_payment'];

            // Préparer les données pour Easypay
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $backendUrl = config('app.url', 'http://localhost:8000');
            
            $easypayData = [
                'order_ref' => $commande->numero_commande ?? 'CMD-' . $commande->id_commande,
                'currency' => 'CDF',
                'amount' => $montantTotal,
                'customer_name' => $commande->utilisateur->nom . ' ' . $commande->utilisateur->prenom,
                'customer_email' => $commande->utilisateur->email,
                'description' => "Paiement commande #{$commande->numero_commande}",
                'success_url' => "{$backendUrl}/api/payments/success?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-success"),
                'error_url' => "{$backendUrl}/api/payments/error?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-error"),
                'cancel_url' => "{$backendUrl}/api/payments/cancel?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-cancel"),
                'language' => $data['language'] ?? 'fr',
                'channels' => $channels, // Envoyer les channels selon le choix
            ];

            // Appeler EasypayService pour initialiser le paiement
            $result = $this->easypayService->initializeTransaction($easypayData);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                    500
                );
            }

            // Enregistrer le paiement en base de données
            DB::beginTransaction();
            try {
                $payment = Payment::create([
                    'id_commande' => $commande->id_commande,
                    'montant' => $montantTotal,
                    'methode' => $methodePayment, // 'carte_bancaire' ou 'mobile_money' selon le choix
                    'statut_payment' => 'en_attente',
                    'transaction_ref' => $result['reference'],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->logger->error('Erreur lors de l\'enregistrement du paiement', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'commande_id' => $commande->id_commande
                ]);
                
                throw $e; // Re-lancer l'exception pour qu'elle soit capturée par le catch principal
            }

            // Si succès, retourner l'URL de redirection
            return $this->createdResponse([
                'payment_id' => $payment->id_payment ?? null,
                'reference' => $result['reference'],
                'redirect_url' => $result['redirect_url'],
            ], 'Paiement initialisé avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de l\'initialisation du paiement',
                ['request' => $request->all()],
                true // Inclure les détails en dev
            );
        }
    }

    /**
     * Handler pour le callback de succès depuis Easypay
     * GET /api/payments/success
     */
    public function success(Request $request)
    {
        try {
            $reference = $request->query('reference');
            $redirectUrl = $request->query('redirect', env('FRONTEND_URL', 'http://localhost:5173') . '/payment-success');
            
            if (!$reference) {
                return $this->errorResponse('Référence de transaction manquante', 400);
            }

            // Vérifier le statut du paiement avec Easypay
            $statusResult = $this->easypayService->checkPaymentStatus($reference);

            if (!$statusResult['success']) {
                return $this->errorResponse(
                    $statusResult['message'] ?? 'Erreur lors de la vérification du paiement',
                    500
                );
            }

            // Mettre à jour le paiement en base de données
            $payment = Payment::where('transaction_ref', $reference)->first();
            
            if ($payment) {
                DB::beginTransaction();
                try {
                    // Mapper le statut Easypay vers notre statut
                    $statutPayment = $this->mapEasypayStatus($statusResult['status']);
                    
                    $payment->update([
                        'statut_payment' => $statutPayment,
                        'methode' => $this->mapEasypayChannel($statusResult['channel'] ?? null),
                        'date_payment' => $statutPayment === 'paye' ? now() : null,
                    ]);

                    // Si le paiement est réussi, finaliser le paiement (mise à jour commande + envoi email)
                    if ($statutPayment === 'paye') {
                        $this->logger->info('Paiement réussi détecté dans success callback, finalisation en cours...', [
                            'reference' => $reference,
                            'payment_id' => $payment->id_payment,
                            'easypay_status' => $statusResult['status']
                        ]);
                        $this->finalizeSuccessfulPayment($payment);
                    } else {
                        $this->logger->info('Paiement non réussi dans success callback', [
                            'reference' => $reference,
                            'statut_payment' => $statutPayment,
                            'easypay_status' => $statusResult['status']
                        ]);
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->logger->error('Erreur lors de la mise à jour du paiement', [
                        'error' => $e->getMessage(),
                        'reference' => $reference
                    ]);
                }
            }

            // Retourner une réponse JSON ou rediriger selon le besoin
            // Rediriger vers le frontend
            if ($statusResult['status'] === 'SUCCESS') {
                return redirect($redirectUrl . '?reference=' . $reference . '&status=success');
            } else {
                return redirect($redirectUrl . '?reference=' . $reference . '&status=failed');
            }

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur dans le handler success',
                ['request' => $request->all()],
                true
            );
        }
    }

    /**
     * Handler pour le callback d'erreur depuis Easypay
     * GET /api/payments/error
     */
    public function error(Request $request)
    {
        try {
            $reference = $request->query('reference');
            
            if ($reference) {
                $payment = Payment::where('transaction_ref', $reference)->first();
                
                if ($payment) {
                    $payment->update([
                        'statut_payment' => 'echec'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Le paiement a échoué',
                'reference' => $reference
            ], 400);

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur dans le handler error',
                ['request' => $request->all()],
                true
            );
        }
    }

    /**
     * Handler pour le callback d'annulation depuis Easypay
     * GET /api/payments/cancel
     */
    public function cancel(Request $request)
    {
        try {
            $reference = $request->query('reference');
            $redirectUrl = $request->query('redirect', env('FRONTEND_URL', 'http://localhost:5173') . '/payment-cancel');
            
            if ($reference) {
                $payment = Payment::where('transaction_ref', $reference)->first();
                
                if ($payment) {
                    $payment->update([
                        'statut_payment' => 'annule'
                    ]);
                }
            }

            return redirect($redirectUrl . '?reference=' . $reference . '&status=cancel');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur dans le handler cancel',
                ['request' => $request->all()],
                true
            );
        }
    }

    /**
     * Handler pour les notifications IPN (Instant Payment Notification) depuis Easypay
     * POST /api/payments/webhook
     */
    public function webhook(Request $request)
    {
        try {
            // Les données viennent en POST depuis Easypay
            $data = $request->all();

            $this->logger->info('Webhook Easypay reçu', ['data' => $data]);

            // Vérifier la structure des données
            if (!isset($data['payment']['reference']) || !isset($data['payment']['status'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données IPN invalides'
                ], 400);
            }

            $reference = $data['payment']['reference'];
            $status = $data['payment']['status'];
            $channel = $data['payment']['channel'] ?? null;

            // Trouver le paiement
            $payment = Payment::where('transaction_ref', $reference)->first();

            if (!$payment) {
                $this->logger->warning('Paiement non trouvé pour la référence', ['reference' => $reference]);
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé'
                ], 404);
            }

            // Mapper le statut Easypay vers notre statut
            $statutPayment = $this->mapEasypayStatus($status);

            // Mettre à jour le paiement
            DB::beginTransaction();
            try {
                $payment->update([
                    'statut_payment' => $statutPayment,
                    'methode' => $this->mapEasypayChannel($channel),
                    'date_payment' => $statutPayment === 'paye' ? now() : null,
                ]);

                // Si le paiement est réussi, finaliser le paiement (mise à jour commande + envoi email)
                if ($statutPayment === 'paye') {
                    $this->finalizeSuccessfulPayment($payment);
                }

                DB::commit();

                $this->logger->info('Paiement mis à jour via webhook', [
                    'reference' => $reference,
                    'status' => $statutPayment
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                $this->logger->error('Erreur lors de la mise à jour du paiement via webhook', [
                    'error' => $e->getMessage(),
                    'reference' => $reference
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ], 500);
            }

            // Retourner un succès à Easypay
            return response()->json([
                'success' => true,
                'message' => 'Notification reçue et traitée'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans le handler webhook', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du webhook'
            ], 500);
        }
    }

    /**
     * Endpoint de test pour simuler un paiement réussi
     * POST /api/payments/test/simulate-success
     * 
     * ATTENTION: À supprimer en production !
     * Cet endpoint permet de tester le flux complet sans avoir à effectuer un vrai paiement.
     */
    public function simulateSuccess(Request $request)
    {
        try {
            $reference = $request->input('reference');
            
            if (!$reference) {
                return response()->json([
                    'success' => false,
                    'message' => 'Référence de transaction manquante'
                ], 400);
            }

            // Vérifier que le paiement existe en base
            $payment = Payment::where('transaction_ref', $reference)->first();
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé avec cette référence'
                ], 404);
            }

            // Simuler un paiement réussi
            DB::beginTransaction();
            try {
                // Mettre à jour le paiement comme s'il était payé
                $payment->update([
                    'statut_payment' => 'paye',
                    'methode' => $request->input('method', 'carte_bancaire'), // 'carte_bancaire' ou 'mobile_money'
                    'date_payment' => now(),
                ]);

                // Finaliser le paiement réussi (mise à jour commande + envoi email)
                $this->finalizeSuccessfulPayment($payment);

                DB::commit();

                // Récupérer la commande pour la réponse
                $commande = Commande::find($payment->id_commande);

                $this->logger->info('Paiement simulé avec succès', [
                    'reference' => $reference,
                    'payment_id' => $payment->id_payment,
                    'commande_id' => $commande->id_commande ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement simulé avec succès (TEST UNIQUEMENT)',
                    'data' => [
                        'payment' => [
                            'id' => $payment->id_payment,
                            'statut' => $payment->statut_payment,
                            'methode' => $payment->methode,
                            'montant' => $payment->montant,
                            'date_payment' => $payment->date_payment,
                        ],
                        'commande' => $commande ? [
                            'id' => $commande->id_commande,
                            'numero_commande' => $commande->numero_commande,
                            'statut' => $commande->statut,
                        ] : null,
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                $this->logger->error('Erreur lors de la simulation du paiement', [
                    'error' => $e->getMessage(),
                    'reference' => $reference
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la simulation',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans simulateSuccess', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapper le statut Easypay vers notre statut de paiement local
     * 
     * @param string $easypayStatus Le statut retourné par Easypay (SUCCESS, CANCELED, DECLINED)
     * @return string Le statut local correspondant (paye, annule, echec, ou en_attente par défaut)
     */
    private function mapEasypayStatus(string $easypayStatus): string
    {
        $statutMapping = [
            'SUCCESS' => 'paye',
            'CANCELED' => 'annule',
            'DECLINED' => 'echec'
        ];

        return $statutMapping[$easypayStatus] ?? 'en_attente';
    }

    /**
     * Mapper le channel Easypay vers notre méthode de paiement locale
     * 
     * @param string|null $easypayChannel Le channel retourné par Easypay (CARD, MOBILE MONEY, etc.)
     * @return string La méthode locale correspondante (carte_bancaire, mobile_money, ou carte_bancaire par défaut)
     */
    private function mapEasypayChannel(?string $easypayChannel): string
    {
        $channelMapping = [
            'CARD' => 'carte_bancaire',
            'MOBILE MONEY' => 'mobile_money',
        ];

        return $channelMapping[$easypayChannel] ?? 'carte_bancaire';
    }

    /**
     * Préparer les channels Easypay et la méthode de paiement locale selon le choix de l'utilisateur
     * 
     * @param string|null $paymentMethod Le moyen de paiement choisi ('credit_card', 'mobile_money', ou null)
     * @return array Tableau contenant 'channels' (pour Easypay) et 'methode_payment' (pour notre base)
     */
    private function preparePaymentChannels(?string $paymentMethod): array
    {
        // Si l'utilisateur a choisi la carte bancaire
        if ($paymentMethod === 'credit_card') {
            return [
                'channels' => [['channel' => 'CREDIT CARD']],
                'methode_payment' => 'carte_bancaire',
            ];
        }
        
        // Si l'utilisateur a choisi le mobile money
        if ($paymentMethod === 'mobile_money') {
            return [
                'channels' => [['channel' => 'MOBILE MONEY']],
                'methode_payment' => 'mobile_money',
            ];
        }
        
        // Si aucun choix spécifique, proposer les deux options
        // La méthode sera mise à jour lors du callback en fonction du choix réel
        return [
            'channels' => [
                ['channel' => 'CREDIT CARD'],
                ['channel' => 'MOBILE MONEY']
            ],
            'methode_payment' => 'carte_bancaire', // Valeur par défaut
        ];
    }

    /**
     * Finaliser un paiement réussi : mettre à jour la commande et envoyer le reçu par email
     * 
     * Cette méthode est appelée lorsqu'un paiement a le statut 'paye'.
     * Elle met à jour le statut de la commande à 'confirmee' et envoie un email
     * avec le reçu PDF en pièce jointe.
     * 
     * @param Payment $payment Le paiement qui a réussi
     * @return void
     */
    private function finalizeSuccessfulPayment(Payment $payment): void
    {
        // Charger la commande avec la relation utilisateur pour l'email
        $commande = Commande::with('utilisateur')->find($payment->id_commande);
        
        if (!$commande) {
            $this->logger->warning('Commande non trouvée pour le paiement', [
                'payment_id' => $payment->id_payment,
                'commande_id' => $payment->id_commande
            ]);
            return;
        }

        // Mettre à jour le statut de la commande si elle est en attente
        // Le statut de commande indique l'état de préparation, pas le paiement
        // Quand le paiement est réussi, on confirme la commande
        $ancienStatut = $commande->statut;
        if ($commande->statut === 'en_attente') {
            $commande->update(['statut' => 'confirmee']);
        }
        
        // Créer une notification pour le client concernant le paiement réussi
        $this->createNotification(
            $commande->id_utilisateur,
            $commande->id_commande,
            'commande',
            'Paiement réussi',
            "Votre paiement pour la commande #{$commande->numero_commande} a été effectué avec succès. Montant payé: " . number_format($payment->montant * 2000, 0, ',', ' ') . " CDF"
        );
        
        // Envoyer le reçu PDF par email
        try {
            $this->logger->info('Envoi de l\'email avec réçu PDF', [
                'payment_id' => $payment->id_payment,
                'commande_id' => $commande->id_commande,
                'email' => $commande->utilisateur->email
            ]);
            
            $this->mailer->to($commande->utilisateur->email)->send(new PaymentReceiptMail($payment));
            
            $this->logger->info('Email avec réçu PDF envoyé avec succès', [
                'payment_id' => $payment->id_payment,
                'email' => $commande->utilisateur->email
            ]);
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer le paiement
            $this->logger->error('Erreur lors de l\'envoi du reçu de paiement', [
                'payment_id' => $payment->id_payment,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Méthode helper pour créer une notification
     * 
     * @param int $idUtilisateur
     * @param int|null $idCommande
     * @param string $type (commande, system, promotion)
     * @param string $titre
     * @param string $message
     */
    private function createNotification($idUtilisateur, $idCommande, $type, $titre, $message)
    {
        try {
            Notification::create([
                'id_utilisateur' => $idUtilisateur,
                'id_commande' => $idCommande,
                'type_notification' => $type,
                'titre' => $titre,
                'message' => $message,
                'lu' => false,
                'date_creation' => now(),
            ]);
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer l'opération principale
            $this->logger->error('Erreur lors de la création de la notification', [
                'user_id' => $idUtilisateur,
                'commande_id' => $idCommande,
                'error' => $e->getMessage(),
            ]);
        }
    }
}