<?php

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\Commande;
use App\Models\Notification;
use App\Models\Parrainage;
use App\Mail\CommandeConfirmationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer la logique métier des commandes
 * 
 * Cette classe contient toute la logique métier des commandes (création, calculs, notifications, etc.)
 * Elle utilise les repositories pour accéder aux données.
 * 
 * Avantages :
 * - Logique métier séparée du controller
 * - Réutilisable partout
 * - Facile à tester
 */
class OrderService
{
    protected $orderRepository;
    protected $cartRepository;
    protected $userRepository;

    /**
     * Constructeur : injection de dépendance
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Créer une commande à partir du panier
     * 
     * @param array $orderData Les données de la commande
     * @param int $userId L'ID de l'utilisateur
     * @return array ['commande' => Commande, 'user' => ['points_balance' => int]]
     */
    public function createOrderFromCart(array $orderData, int $userId): array
    {
        DB::beginTransaction();
        try {
            // Récupérer le panier actif
            $panier = $this->cartRepository->getCart($userId);
            
            if (!$panier || $panier->articles->isEmpty()) {
                throw new \Exception('Votre panier est vide');
            }

            // Calculer les montants
            $calculations = $this->calculateOrderAmounts($panier, $orderData, $userId);
            
            // IMPORTANT: Ne pas déduire les points ici !
            // Les points seront déduits uniquement lorsque le paiement sera confirmé (dans PaymentController)
            // Cela évite de perdre des points si le paiement échoue

            // Mettre à jour le panier en commande
            $panier->update([
                'statut' => 'en_attente',
                'type_commande' => $orderData['type_commande'],
                'adresse_livraison' => $orderData['adresse_livraison'] ?? null,
                'points_utilises' => $calculations['pointsUtilises'],
                'reduction_points' => $calculations['reductionPoints'],
                'frais_livraison' => $calculations['fraisLivraison'],
                'montant_total' => $calculations['montantFinal'],
                'commentaire' => $orderData['commentaire'] ?? null,
                'instructions_speciales' => $orderData['instructions_speciales'] ?? null,
                'heure_arrivee_prevue' => $orderData['heure_arrivee_prevue'] ?? null,
                'date_modification' => now(),
            ]);

            DB::commit();

            // Recharger la commande avec les relations
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                ->find($panier->id_commande);

            // Gérer le parrainage si c'est la première commande
            // Note: La commande doit avoir la relation utilisateur chargée
            if ($commande->utilisateur) {
                $this->handlePremiereCommandeParrainage($commande);
            }

            // Envoyer l'email de confirmation
            $this->sendOrderConfirmationEmail($commande);

            // Créer les notifications
            $this->createOrderNotifications($commande);

            // Récupérer le nouveau solde de points
            $user = $this->userRepository->findById($userId);
            $pointsBalance = $user ? $user->points_balance : 0;

            return [
                'commande' => $commande,
                'user' => [
                    'points_balance' => $pointsBalance,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la commande', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculer les montants de la commande
     * 
     * @param Commande $panier Le panier
     * @param array $orderData Les données de la commande
     * @param int $userId L'ID de l'utilisateur
     * @return array ['montantTotal', 'pointsUtilises', 'reductionPoints', 'fraisLivraison', 'montantFinal']
     */
    protected function calculateOrderAmounts(Commande $panier, array $orderData, int $userId): array
    {
        $montantTotal = $panier->getTotal();
        $pointsUtilises = $orderData['points_utilises'] ?? 0;
        $reductionPoints = 0;
        $fraisLivraison = 0;

        // Calculer les frais de livraison si c'est une livraison
        if ($orderData['type_commande'] === 'livraison') {
            $fraisLivraison = 2000; // 2000 FC
        }

        // Gérer les points de fidélité si utilisés
        if ($pointsUtilises > 0) {
            $user = $this->userRepository->findById($userId);
            
            if (!$user || $user->points_balance < $pointsUtilises) {
                throw new \Exception('Vous n\'avez pas assez de points. Votre solde: ' . ($user->points_balance ?? 0));
            }

            // Calculer la réduction (1 point = 67 FC)
            $valeurPoint = 67;
            $reductionPoints = $pointsUtilises * $valeurPoint;

            // La réduction ne peut pas dépasser le montant total
            if ($reductionPoints > $montantTotal) {
                $reductionPoints = $montantTotal;
            }
        }

        // Calculer le montant final
        $montantFinal = $montantTotal + $fraisLivraison - $reductionPoints;

        // S'assurer que le montant final n'est pas négatif
        if ($montantFinal < 0) {
            $montantFinal = 0;
        }

        return [
            'montantTotal' => $montantTotal,
            'pointsUtilises' => $pointsUtilises,
            'reductionPoints' => $reductionPoints,
            'fraisLivraison' => $fraisLivraison,
            'montantFinal' => $montantFinal,
        ];
    }

    /**
     * Gérer le parrainage si c'est la première commande
     * 
     * @param Commande $commande La commande
     * @return void
     */
    protected function handlePremiereCommandeParrainage(Commande $commande): void
    {
        try {
            // Vérifier si c'est la première commande de l'utilisateur
            // Exclure la commande actuelle du comptage
            $nombreCommandes = Commande::where('id_utilisateur', $commande->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->where('statut', '!=', 'annulee')
                ->where('id_commande', '!=', $commande->id_commande)
                ->count();

            // Si c'est la première commande, vérifier le parrainage
            if ($nombreCommandes === 0) {
                $parrainage = Parrainage::where('id_filleul', $commande->id_utilisateur)
                    ->where('premiere_commande_faite', false)
                    ->first();

                if ($parrainage) {
                    // Marquer la première commande comme faite
                    $parrainage->update(['premiere_commande_faite' => true]);

                    // Récupérer les points de première commande depuis parametres_fidelite
                    $parametresFidelite = DB::table('parametres_fidelite')
                        ->where('actif', true)
                        ->orderBy('date_debut_application', 'desc')
                        ->first();

                    $pointsPremiereCommande = $parametresFidelite ? $parametresFidelite->points_premiere_commande : 20;

                    // Attribuer les points au parrain
                    $this->userRepository->incrementPoints($parrainage->id_parrain, $pointsPremiereCommande);

                    // Envoyer une notification au parrain
                    Notification::create([
                        'id_utilisateur' => $parrainage->id_parrain,
                        'id_commande' => null,
                        'type_notification' => 'system',
                        'titre' => 'Première commande de votre filleul',
                        'message' => "Votre filleul a effectué sa première commande ! Vous avez gagné {$pointsPremiereCommande} points.",
                        'lu' => false,
                        'date_creation' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            Log::error('Erreur lors de la gestion du parrainage', [
                'commande_id' => $commande->id_commande,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer l'email de confirmation de commande
     * 
     * @param Commande $commande La commande
     * @return void
     */
    protected function sendOrderConfirmationEmail(Commande $commande): void
    {
        try {
            Mail::to($commande->utilisateur->email)
                ->send(new CommandeConfirmationMail($commande));
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            Log::error('Erreur lors de l\'envoi de l\'email de confirmation de commande', [
                'commande_id' => $commande->id_commande,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Créer les notifications pour une commande
     * 
     * @param Commande $commande La commande
     * @return void
     */
    protected function createOrderNotifications(Commande $commande): void
    {
        try {
            // Notification pour le client
            Notification::create([
                'id_utilisateur' => $commande->id_utilisateur,
                'id_commande' => $commande->id_commande,
                'type_notification' => 'commande',
                'titre' => 'Commande passée',
                'message' => "Votre commande #{$commande->numero_commande} a été passée avec succès. Montant total: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF",
                'lu' => false,
                'date_creation' => now(),
            ]);

            // Notifier tous les employés de la nouvelle commande
            $this->notifyEmployeesOfNewOrder($commande);
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            Log::error('Erreur lors de la création des notifications', [
                'commande_id' => $commande->id_commande,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifier tous les employés d'une nouvelle commande
     * 
     * @param Commande $commande La commande
     * @return void
     */
    protected function notifyEmployeesOfNewOrder(Commande $commande): void
    {
        try {
            $employes = \App\Models\Utilisateur::whereIn('role', ['employe', 'gerant', 'admin'])
                ->where('statut_compte', 'actif')
                ->get();

            foreach ($employes as $employe) {
                Notification::create([
                    'id_utilisateur' => $employe->id_utilisateur,
                    'id_commande' => $commande->id_commande,
                    'type_notification' => 'commande',
                    'titre' => 'Nouvelle commande',
                    'message' => "Nouvelle commande #{$commande->numero_commande} de {$commande->utilisateur->prenom} {$commande->utilisateur->nom}. Montant: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF",
                    'lu' => false,
                    'date_creation' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            Log::error('Erreur lors de la notification des employés', [
                'commande_id' => $commande->id_commande,
                'error' => $e->getMessage(),
            ]);
        }
    }
}




