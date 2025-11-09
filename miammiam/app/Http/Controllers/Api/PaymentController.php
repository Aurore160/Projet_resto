<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\InitializePaymentRequest;
use App\Strategies\Payment\PaymentContext;
use App\Strategies\Payment\EasypayStrategy;
use App\Services\EasypayService;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Models\Commande;
use App\Models\Payment;
use App\Models\Notification;
use App\Mail\PaymentReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Mail\Mailer;

class PaymentController extends Controller
{
    use HandlesApiResponses;
    
    protected $paymentContext;
    protected $easypayService;
    protected $orderRepository;
    protected $logger;
    protected $mailer;

    /**
     * Constructeur : injection de dépendance
     * 
     * PaymentContext sera utilisé pour gérer les stratégies de paiement.
     * EasypayService est toujours nécessaire pour créer EasypayStrategy.
     * OrderRepository pour valider l'accès aux commandes.
     */
    public function __construct(
        PaymentContext $paymentContext,
        EasypayService $easypayService,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        Mailer $mailer
    ) {
        $this->paymentContext = $paymentContext;
        $this->easypayService = $easypayService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Initialiser un paiement pour une commande
     * 
     * POST /api/payments/initialize
     * 
     * Cette méthode est maintenant simplifiée grâce à l'extraction de méthodes privées (KISS).
     */
    public function initialize(InitializePaymentRequest $request)
    {
        try {
            $user = $request->user();
            $data = $request->validated();
            
            // Valider et récupérer la commande
            $commande = $this->validateAndGetOrder($data['commande_id'], $user);
            if ($commande instanceof \Illuminate\Http\JsonResponse) {
                return $commande; // Erreur retournée
            }

            // Mettre à jour la commande si nécessaire
            $this->updateOrderIfNeeded($commande, $data);

            // Calculer le montant total
            $montantTotal = $commande->montant_total ?? $commande->getTotal();

            // Configurer la stratégie de paiement
            $this->paymentContext->setStrategy(new EasypayStrategy($this->easypayService));

            // Préparer les données pour le paiement
            $paymentData = $this->preparePaymentData($commande, $montantTotal, $data);

            // Initialiser le paiement avec la stratégie
            $result = $this->paymentContext->initializePayment($paymentData);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                    500
                );
            }

            // Récupérer la référence de transaction depuis la stratégie
            $transactionRef = $this->paymentContext->getTransactionReference() ?? $result['reference'] ?? null;
            
            if (!$transactionRef) {
                return $this->errorResponse('Référence de transaction non disponible', 500);
            }

            // Déterminer la méthode de paiement selon le choix
            $methodePayment = $this->mapPaymentMethod($data['payment_method'] ?? null);

            // Enregistrer le paiement en base de données
            DB::beginTransaction();
            try {
                $payment = Payment::create([
                    'id_commande' => $commande->id_commande,
                    'montant' => $montantTotal,
                    'methode' => $methodePayment, // 'carte_bancaire' ou 'mobile_money' selon le choix
                    'statut_payment' => 'en_attente',
                    'transaction_ref' => $transactionRef,
                ]);

                // Déduire les points de fidélité utilisés AVANT la redirection vers Easypay
                if ($commande->points_utilises > 0) {
                    try {
                        $commande->load('utilisateur');
                        $user = $commande->utilisateur;
                        if ($user && $user->points_balance >= $commande->points_utilises) {
                            $user->decrement('points_balance', $commande->points_utilises);
                            $this->logger->info('Points de fidélité déduits lors de l\'initialisation du paiement', [
                                'user_id' => $user->id_utilisateur,
                                'points_deduits' => $commande->points_utilises,
                                'nouveau_solde' => $user->points_balance,
                                'commande_id' => $commande->id_commande
                            ]);

                            // Enregistrer la transaction de points
                            DB::table('point_transaction')->insert([
                                'id_utilisateur' => $user->id_utilisateur,
                                'type_transaction' => 'utilisation',
                                'source' => 'commande',
                                'montant_points' => -$commande->points_utilises, // Négatif car c'est une déduction
                                'solde_apres_transaction' => $user->points_balance,
                                'id_reference' => $commande->id_commande,
                                'description' => "Utilisation de {$commande->points_utilises} point(s) pour la commande #{$commande->numero_commande}",
                                'date_transaction' => now(),
                            ]);
                        } else {
                            $this->logger->warning('Impossible de déduire les points: solde insuffisant ou utilisateur introuvable', [
                                'user_id' => $user->id_utilisateur ?? null,
                                'points_a_deduire' => $commande->points_utilises,
                                'solde_actuel' => $user->points_balance ?? null,
                                'commande_id' => $commande->id_commande
                            ]);
                            // Ne pas bloquer la redirection même si les points ne peuvent pas être déduits
                        }
                    } catch (\Exception $e) {
                        // Log l'erreur mais ne fait pas échouer l'initialisation du paiement
                        $this->logger->error('Erreur lors de la déduction des points de fidélité lors de l\'initialisation', [
                            'commande_id' => $commande->id_commande,
                            'points_utilises' => $commande->points_utilises,
                            'error' => $e->getMessage()
                        ]);
                        // Ne pas bloquer la redirection même si les points ne peuvent pas être déduits
                    }
                }

                // Vider le panier backend (s'il existe encore) APRÈS l'initialisation du paiement
                // Le panier devrait déjà être transformé en commande, mais on s'assure qu'il n'y a plus de panier actif
                $panierActif = Commande::where('id_utilisateur', $user->id_utilisateur)
                    ->where('statut', 'panier')
                    ->first();
                
                if ($panierActif) {
                    // Supprimer les articles du panier
                    \DB::table('commande_articles')
                        ->where('id_commande', $panierActif->id_commande)
                        ->delete();
                    
                    // Supprimer le panier
                    $panierActif->delete();
                }

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

            // Envoyer l'email avec le réçu PDF AVANT la redirection vers Easypay
            // Augmenter le timeout PHP pour cette requête spécifique pour éviter le timeout de 60 secondes
            try {
                $this->logger->info('Envoi de l\'email avec réçu PDF lors de l\'initialisation du paiement EasyPay', [
                    'payment_id' => $payment->id_payment,
                    'commande_id' => $commande->id_commande,
                    'email' => $commande->utilisateur->email
                ]);
                
                // Augmenter le timeout PHP pour cette requête spécifique (120 secondes)
                $originalTimeout = ini_get('max_execution_time');
                set_time_limit(120); // 2 minutes pour la génération du PDF et l'envoi de l'email
                
                // Envoyer l'email de manière synchrone pour s'assurer qu'il est envoyé
                // Utiliser send() au lieu de queue() pour garantir l'envoi immédiat
                Mail::to($commande->utilisateur->email)->send(new PaymentReceiptMail($payment));
                
                // Restaurer le timeout original
                if ($originalTimeout !== false && $originalTimeout !== '0') {
                    set_time_limit((int)$originalTimeout);
                }
                
                $this->logger->info('Email avec réçu PDF envoyé avec succès lors de l\'initialisation du paiement', [
                    'payment_id' => $payment->id_payment,
                    'email' => $commande->utilisateur->email
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais ne fait pas échouer l'initialisation du paiement
                $this->logger->error('Erreur lors de l\'envoi du réçu de paiement lors de l\'initialisation', [
                    'payment_id' => $payment->id_payment,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Ne pas bloquer la redirection même si l'email échoue
            }

            // Récupérer l'URL de redirection depuis la stratégie
            $redirectUrl = $this->paymentContext->getRedirectUrl() ?? $result['redirect_url'] ?? null;
            
            if (!$redirectUrl) {
                return $this->errorResponse('URL de redirection non disponible', 500);
            }

            // Si succès, retourner l'URL de redirection
            return $this->successResponse([
                'payment_id' => $payment->id_payment ?? null,
                'reference' => $transactionRef,
                'redirect_url' => $redirectUrl,
            ], 'Paiement initialisé avec succès', 201);

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

            return $this->errorResponse('Le paiement a échoué', 400, ['reference' => $reference]);

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
                return $this->errorResponse('Données IPN invalides', 400);
            }

            $reference = $data['payment']['reference'];
            $status = $data['payment']['status'];
            $channel = $data['payment']['channel'] ?? null;

            // Trouver le paiement
            $payment = Payment::where('transaction_ref', $reference)->first();

            if (!$payment) {
                $this->logger->warning('Paiement non trouvé pour la référence', ['reference' => $reference]);
                return $this->notFoundResponse('Paiement');
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
                
                return $this->errorResponse('Erreur lors de la mise à jour', 500);
            }

            // Retourner un succès à Easypay
            return $this->successResponse(null, 'Notification reçue et traitée');

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans le handler webhook', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return $this->errorResponse('Erreur lors du traitement du webhook', 500);
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
     * Mapper la méthode de paiement du frontend vers notre format local
     * 
     * @param string|null $paymentMethod Le moyen de paiement choisi ('credit_card', 'mobile_money', ou null)
     * @return string La méthode locale ('carte_bancaire', 'mobile_money', ou 'carte_bancaire' par défaut)
     */
    private function mapPaymentMethod(?string $paymentMethod): string
    {
        $mapping = [
            'credit_card' => 'carte_bancaire',
            'mobile_money' => 'mobile_money',
        ];

        return $mapping[$paymentMethod] ?? 'carte_bancaire';
    }

    /**
     * Valider et récupérer une commande pour le paiement
     * 
     * @param int $commandeId L'ID de la commande
     * @param \App\Models\Utilisateur $user L'utilisateur connecté
     * @return Commande|\Illuminate\Http\JsonResponse La commande ou une réponse d'erreur
     */
    private function validateAndGetOrder(int $commandeId, $user)
    {
        // Récupérer la commande
        $commande = Commande::with('utilisateur')
            ->where('id_commande', $commandeId)
            ->first();

        if (!$commande) {
            return $this->notFoundResponse('Commande');
        }

        // Vérifier que la commande appartient à l'utilisateur connecté (DRY)
        if (!$this->orderRepository->belongsToUser($commande->id_commande, $user->id_utilisateur)) {
            return $this->errorResponse('Vous n\'êtes pas autorisé à payer cette commande', 403);
        }

        // Vérifier que l'utilisateur de la commande est bien chargé
        if (!$commande->utilisateur) {
            $commande->load('utilisateur');
            if (!$commande->utilisateur) {
                return $this->errorResponse('Utilisateur de la commande introuvable', 404);
            }
        }

        // Vérifier que la commande n'est pas déjà payée
        $existingPayment = Payment::where('id_commande', $commande->id_commande)
            ->where('statut_payment', 'paye')
            ->first();

        if ($existingPayment) {
            return $this->errorResponse('Cette commande est déjà payée', 400);
        }

        return $commande;
    }

    /**
     * Mettre à jour la commande si des données sont fournies
     * 
     * @param Commande $commande La commande à mettre à jour
     * @param array $data Les données de la requête
     * @return void
     */
    private function updateOrderIfNeeded(Commande $commande, array $data): void
    {
        $updateData = [];
        
        if (isset($data['adresse_livraison']) && !empty($data['adresse_livraison'])) {
            $updateData['adresse_livraison'] = $data['adresse_livraison'];
        }
        
        if (isset($data['montant_total']) && $data['montant_total'] > 0) {
            $updateData['montant_total'] = $data['montant_total'];
        }
        
        // Mettre à jour les points utilisés si fournis
        if (isset($data['points_utilises']) && $data['points_utilises'] >= 0) {
            $updateData['points_utilises'] = (int) $data['points_utilises'];
        }
        
        if (!empty($updateData)) {
            $updateData['date_modification'] = now();
            $commande->update($updateData);
            $commande->refresh();
        }
    }

    /**
     * Préparer les données pour l'initialisation du paiement
     * 
     * @param Commande $commande La commande
     * @param float $montantTotal Le montant total
     * @param array $requestData Les données de la requête
     * @return array Les données formatées pour le paiement
     */
    private function preparePaymentData(Commande $commande, float $montantTotal, array $requestData): array
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $backendUrl = config('app.url', 'http://localhost:8000');
        
        return [
            'order_ref' => $commande->numero_commande ?? 'CMD-' . $commande->id_commande,
            'currency' => 'CDF',
            'amount' => $montantTotal,
            'customer_name' => $commande->utilisateur->nom . ' ' . $commande->utilisateur->prenom,
            'customer_email' => $commande->utilisateur->email,
            'description' => "Paiement commande #{$commande->numero_commande}",
            'success_url' => "{$backendUrl}/api/payments/success?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-success"),
            'error_url' => "{$backendUrl}/api/payments/error?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-error"),
            'cancel_url' => "{$backendUrl}/api/payments/cancel?reference={reference}&redirect=" . urlencode("{$frontendUrl}/payment-cancel"),
            'language' => $requestData['language'] ?? 'fr',
            'payment_method' => $requestData['payment_method'] ?? null,
        ];
    }

    /**
     * Finaliser un paiement réussi : mettre à jour la commande et envoyer le reçu par email
     * 
     * Cette méthode est appelée lorsqu'un paiement a le statut 'paye'.
     * Elle met à jour le statut de la commande à 'confirmee' et envoie un email
     * avec le reçu PDF en pièce jointe.
     * 
     * IMPORTANT: Les points de fidélité sont déduits ICI, uniquement après confirmation du paiement.
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

        // Note: Les points de fidélité sont maintenant déduits lors de l'initialisation du paiement
        // (dans la méthode initialize()) avant la redirection vers Easypay, pas ici après le paiement réussi.

        // Attribuer des points de fidélité basés sur les dépenses (1 point pour chaque 1000 FC dépensés)
        try {
            $user = $commande->utilisateur;
            if ($user) {
                // Calculer les points gagnés : 1 point pour chaque 1000 FC dépensés
                // Le montant total de la commande (après réduction des points)
                $montantDepense = $commande->montant_total;
                $pointsGagnes = floor($montantDepense / 1000);
                
                if ($pointsGagnes > 0) {
                    $user->increment('points_balance', $pointsGagnes);
                    $user->refresh(); // Recharger pour obtenir le nouveau solde
                    
                    $this->logger->info('Points de fidélité attribués après paiement réussi', [
                        'user_id' => $user->id_utilisateur,
                        'montant_depense' => $montantDepense,
                        'points_gagnes' => $pointsGagnes,
                        'nouveau_solde' => $user->points_balance,
                        'commande_id' => $commande->id_commande
                    ]);

                    // Créer une transaction de points dans la table point_transaction
                    DB::table('point_transaction')->insert([
                        'id_utilisateur' => $user->id_utilisateur,
                        'type_transaction' => 'gain',
                        'source' => 'commande',
                        'montant_points' => $pointsGagnes,
                        'solde_apres_transaction' => $user->points_balance,
                        'id_reference' => $commande->id_commande,
                        'description' => "Points commande #{$commande->numero_commande} (1 point pour chaque 1000 FC dépensés)",
                        'date_transaction' => now(),
                    ]);

                    // Créer une notification pour informer l'utilisateur
                    $this->createNotification(
                        $user->id_utilisateur,
                        $commande->id_commande,
                        'system',
                        'Points de fidélité gagnés',
                        "Félicitations ! Vous avez gagné {$pointsGagnes} point(s) de fidélité pour votre commande #{$commande->numero_commande}. Votre nouveau solde est de {$user->points_balance} point(s)."
                    );
                }
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer le paiement
            $this->logger->error('Erreur lors de l\'attribution des points de fidélité', [
                'commande_id' => $commande->id_commande,
                'error' => $e->getMessage()
            ]);
        }

        // Mettre à jour le statut de la commande si elle est en attente
        // Le statut de commande indique l'état de préparation, pas le paiement
        // Quand le paiement est réussi, on confirme la commande (PAS "livrée" !)
        // Le statut "livrée" sera défini plus tard par un employé/livreur
        $ancienStatut = $commande->statut;
        if ($commande->statut === 'en_attente') {
            $commande->update(['statut' => 'confirmee']);
            $this->logger->info('Statut de la commande mis à jour après paiement réussi', [
                'commande_id' => $commande->id_commande,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => 'confirmee'
            ]);
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
