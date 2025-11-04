<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\AssignOrder;
use App\Models\Commande;
use App\Models\Payment;
use App\Models\Utilisateur;
use App\Models\Parrainage;
use App\Models\Notification;
use App\Mail\CommandeConfirmationMail;
use App\Mail\PaymentReceiptMail;
use App\Services\EasypayService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Lister toutes les commandes de l'utilisateur connecté
     * (exclut les paniers)
     * 
     * GET /api/orders
     * 
     * Paramètres optionnels :
     * - status : Filtrer par statut (en_attente, confirmee, en_preparation, pret, livree, annulee)
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètre optionnel de filtrage par statut
            $status = $request->query('status');
            
            // Requête de base : toutes les commandes de l'utilisateur sauf les paniers
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->with(['articles.menuItem']); // Charger les relations
            
            // Filtrer par statut si fourni
            if ($status) {
                $query->where('statut', $status);
            }
            
            // Trier par date (plus récent en premier)
            $commandes = $query->orderBy('date_commande', 'desc')->get();
            
            // Formater la réponse (format identique aux autres méthodes du projet)
            $commandesFormatees = $commandes->map(function ($commande) {
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'nb_articles' => $commande->getTotalArticles(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir un résumé des commandes par statut
     * 
     * GET /api/orders/summary
     * 
     * Retourne :
     * - Nombre de commandes par statut
     * - Total de commandes (hors paniers)
     * - Montant total dépensé
     * - Statistiques additionnelles
     */
    public function summary(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Définir tous les statuts possibles (hors panier)
            $statutsPossibles = ['en_attente', 'confirmee', 'en_preparation', 'pret', 'livree', 'annulee'];
            
            // Compter les commandes par statut
            $commandesParStatut = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->select('statut', DB::raw('COUNT(*) as nombre'))
                ->groupBy('statut')
                ->pluck('nombre', 'statut')
                ->toArray();
            
            // Initialiser tous les statuts à 0
            $statistiques = [];
            foreach ($statutsPossibles as $statut) {
                $statistiques[$statut] = $commandesParStatut[$statut] ?? 0;
            }
            
            // Calculer le total de commandes
            $totalCommandes = array_sum($statistiques);
            
            // Calculer le montant total dépensé (seulement pour les commandes livrées)
            $montantTotalDepense = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', 'livree')
                ->sum('montant_total');
            
            // Compter les commandes actives (en cours : pas encore livrées ni annulées)
            $commandesActives = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->whereNotIn('statut', ['livree', 'annulee'])
                ->count();
            
            // Retourner le résumé formaté
            return response()->json([
                'success' => true,
                'data' => [
                    'par_statut' => $statistiques,
                    'totaux' => [
                        'total_commandes' => $totalCommandes,
                        'commandes_actives' => $commandesActives,
                        'montant_total_depense' => (float) $montantTotalDepense,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du résumé des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé des commandes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister les commandes actives (en cours) de l'utilisateur connecté
     * 
     * GET /api/orders/active
     * 
     * Retourne toutes les commandes qui ne sont pas encore terminées :
     * - en_attente
     * - confirmee
     * - en_preparation
     * - pret
     * 
     * Exclut : livree, annulee, panier
     * 
     * Utile pour le suivi dynamique en temps réel
     */
    public function active(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètre optionnel de filtrage par statut spécifique
            $status = $request->query('status');
            
            // Statuts considérés comme "actifs" (en cours)
            $statutsActifs = ['en_attente', 'confirmee', 'en_preparation', 'pret'];
            
            // Requête de base : commandes actives de l'utilisateur
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->whereIn('statut', $statutsActifs)
                ->with(['articles.menuItem']); // Charger les relations
            
            // Filtrer par statut spécifique si fourni (mais seulement parmi les statuts actifs)
            if ($status && in_array($status, $statutsActifs)) {
                $query->where('statut', $status);
            }
            
            // Trier par date de modification (plus récent en premier) pour voir les mises à jour
            $commandes = $query->orderBy('date_modification', 'desc')
                ->orderBy('date_commande', 'desc')
                ->get();
            
            // Formater la réponse avec des informations de suivi supplémentaires
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le pourcentage de progression selon le statut
                $progression = $this->calculerProgression($commande->statut);
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                    'nb_articles' => $commande->getTotalArticles(),
                    'progression' => $progression, // Pourcentage de progression (0-100)
                    'adresse_livraison' => $commande->adresse_livraison,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $commandesFormatees->count(),
                    'message' => 'Commandes actives récupérées avec succès',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes actives', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes actives',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculer le pourcentage de progression d'une commande selon son statut
     * 
     * @param string $statut Le statut de la commande
     * @return int Pourcentage de progression (0-100)
     */
    private function calculerProgression(string $statut): int
    {
        $progressionParStatut = [
            'panier' => 0,
            'en_attente' => 10,        // Commande créée, en attente de paiement
            'confirmee' => 30,          // Paiement confirmé
            'en_preparation' => 60,     // En cours de préparation
            'pret' => 90,               // Prête à être livrée/servie
            'livree' => 100,            // Livrée
            'annulee' => 0,              // Annulée
        ];

        return $progressionParStatut[$statut] ?? 0;
    }

    /**
     * Obtenir le libellé lisible d'un statut
     * 
     * @param string $statut Le statut de la commande
     * @return string Le libellé lisible en français
     */
    private function getStatutLisible(string $statut): string
    {
        $libelles = [
            'panier' => 'Panier',
            'en_attente' => 'En attente',
            'confirmee' => 'Confirmée',
            'en_preparation' => 'En préparation',
            'pret' => 'Prête',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
        ];

        return $libelles[$statut] ?? ucfirst(str_replace('_', ' ', $statut));
    }

    /**
     * Lister les commandes récemment mises à jour
     * 
     * GET /api/orders/recent-updates
     * 
     * Retourne les commandes modifiées récemment (utile pour les notifications visuelles)
     * 
     * Paramètres optionnels :
     * - hours : Nombre d'heures en arrière pour chercher les mises à jour (défaut: 24)
     * - status : Filtrer par statut spécifique
     * 
     * Utile pour :
     * - Afficher des notifications "Nouvelle mise à jour de votre commande"
     * - Détecter les changements de statut récents
     * - Alerter l'utilisateur des commandes qui ont changé
     */
    public function recentUpdates(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètre optionnel : nombre d'heures (défaut: 24 heures)
            $hours = (int) ($request->query('hours', 24));
            // Limiter entre 1 heure et 168 heures (7 jours) pour éviter les abus
            $hours = max(1, min(168, $hours));
            
            // Paramètre optionnel de filtrage par statut
            $status = $request->query('status');
            
            // Date limite pour les mises à jour récentes
            $dateLimite = now()->subHours($hours);
            
            // Requête de base : commandes modifiées récemment (hors paniers)
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->where('date_modification', '>=', $dateLimite)
                ->with(['articles.menuItem']); // Charger les relations
            
            // Filtrer par statut si fourni
            if ($status) {
                $query->where('statut', $status);
            }
            
            // Trier par date de modification (plus récent en premier)
            $commandes = $query->orderBy('date_modification', 'desc')
                ->orderBy('date_commande', 'desc')
                ->get();
            
            // Formater la réponse avec des informations de notification
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le temps écoulé depuis la dernière modification
                $tempsEcoule = $commande->date_modification->diffForHumans();
                
                // Calculer le pourcentage de progression
                $progression = $this->calculerProgression($commande->statut);
                
                // Déterminer si c'est une nouvelle mise à jour (moins de 1 heure)
                $isNouvelleMiseAJour = $commande->date_modification->isAfter(now()->subHour());
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                    'temps_ecoule' => $tempsEcoule, // "il y a 2 heures", "il y a 5 minutes"
                    'progression' => $progression,
                    'nb_articles' => $commande->getTotalArticles(),
                    'is_nouvelle_mise_a_jour' => $isNouvelleMiseAJour, // Pour affichage badge "Nouveau"
                    'adresse_livraison' => $commande->adresse_livraison,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $commandesFormatees->count(),
                    'periode_heures' => $hours,
                    'date_limite' => $dateLimite->format('Y-m-d H:i:s'),
                    'nouvelles_mises_a_jour' => $commandesFormatees->where('is_nouvelle_mise_a_jour', true)->count(),
                    'message' => 'Commandes récemment mises à jour récupérées avec succès',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes récemment mises à jour', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes récemment mises à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $utilisateur = $request->user();
            $data = $request->validated();

            // Récupérer le panier actif de l'utilisateur
            $panier = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                              ->where('statut', 'panier')
                              ->with('articles')
                              ->first();

            if (!$panier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide',
                ], 404);
            }

            // Vérifier que le panier contient des articles
            if ($panier->articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide. Ajoutez des articles avant de passer commande.',
                ], 400);
            }

            // Calculer le montant total du panier
            $montantTotal = $panier->getTotal();
            $pointsUtilises = $data['points_utilises'] ?? 0;
            $reductionPoints = 0;
            $fraisLivraison = 0;

            // Calculer les frais de livraison si c'est une livraison
            if ($data['type_commande'] === 'livraison') {
                $fraisLivraison = 2000; // 2000 FC (Franc Congolais) pour la livraison
            }

            // Gérer les points de fidélité si utilisés
            if ($pointsUtilises > 0) {
                // Vérifier que l'utilisateur a assez de points
                if ($utilisateur->points_balance < $pointsUtilises) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas assez de points. Votre solde: ' . $utilisateur->points_balance,
                    ], 400);
                }

                // Calculer la réduction (15 points = 1000 FC)
                $valeurPoint = 1000; // 1 point = 1000/15 ≈ 66.67 FC (Franc Congolais)
                $reductionPoints = ($pointsUtilises * $valeurPoint) / 15;

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

            // Déduire les points utilisés du solde de l'utilisateur
            if ($pointsUtilises > 0) {
                $utilisateur->points_balance -= $pointsUtilises;
                $utilisateur->save();
            }

            // Mettre à jour le panier en commande
            $panier->update([
                'statut' => 'en_attente',
                'type_commande' => $data['type_commande'],
                'adresse_livraison' => $data['adresse_livraison'] ?? null,
                'points_utilises' => $pointsUtilises,
                'reduction_points' => $reductionPoints,
                'frais_livraison' => $fraisLivraison,
                'montant_total' => $montantFinal,
                'commentaire' => $data['commentaire'] ?? null,
                'instructions_speciales' => $data['instructions_speciales'] ?? null,
                'heure_arrivee_prevue' => $data['heure_arrivee_prevue'] ?? null,
                'date_modification' => now(),
            ]);

            // Le trigger va générer automatiquement le numero_commande

            DB::commit();

            // Charger les relations pour la réponse et l'email
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                                ->find($panier->id_commande);

            // Vérifier si c'est la première commande et gérer le parrainage
            $this->handlePremiereCommandeParrainage($commande, $utilisateur);

            // Envoyer l'email de confirmation
            try {
                Mail::to($commande->utilisateur->email)->send(new CommandeConfirmationMail($commande));
            } catch (\Exception $e) {
                // Log l'erreur mais ne fait pas échouer la commande
                \Log::error('Erreur lors de l\'envoi de l\'email de confirmation', [
                    'commande_id' => $commande->id_commande,
                    'error' => $e->getMessage(),
                ]);
            }

            // Créer une notification pour le client
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                'Commande passée',
                "Votre commande #{$commande->numero_commande} a été passée avec succès. Montant total: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF"
            );

            // Notifier tous les employés de la nouvelle commande
            $this->notifyEmployeesOfNewOrder($commande);

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès !',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'date_commande' => $commande->date_commande,
                        'articles' => $commande->articles->map(function ($article) {
                            return [
                                'id' => $article->id_commande_article,
                                'plat' => $article->menuItem->nom ?? 'Plat supprimé',
                                'quantite' => $article->quantite,
                                'prix_unitaire' => $article->prix_unitaire,
                                'sous_total' => $article->getSousTotal(),
                            ];
                        }),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la création de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Lister les commandes en attente (pour les employés)
     * 
     * GET /api/orders/pending
     * 
     * Retourne toutes les commandes avec le statut "en_attente"
     * Accessible uniquement aux employés (employe, gerant, admin)
     */
    public function pending(Request $request)
    {
        try {
            // Récupérer toutes les commandes en attente (hors paniers)
            $commandes = Commande::where('statut', 'en_attente')
                ->with(['articles.menuItem', 'utilisateur']) // Charger les relations
                ->orderBy('date_commande', 'asc') // Plus anciennes en premier (FIFO)
                ->get();
            
            // Formater la réponse avec tous les détails nécessaires pour les employés
            $commandesFormatees = $commandes->map(function ($commande) {
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'description' => $article->menuItem->description ?? null,
                            'prix_unitaire' => $article->prix_unitaire,
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'sous_total' => $article->getSousTotal(),
                        'instructions' => $article->instructions ?? null,
                    ];
                });

                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'commentaire' => $commande->commentaire,
                    'instructions_speciales' => $commande->instructions_speciales,
                    'nb_articles' => $commande->getTotalArticles(),
                    'client' => [
                        'id_utilisateur' => $commande->utilisateur->id_utilisateur ?? null,
                        'nom' => $commande->utilisateur->nom ?? 'Client supprimé',
                        'prenom' => $commande->utilisateur->prenom ?? '',
                        'email' => $commande->utilisateur->email ?? null,
                        'telephone' => $commande->utilisateur->telephone ?? null,
                    ],
                    'articles' => $articlesFormates,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $commandesFormatees->count(),
                    'message' => 'Commandes en attente récupérées avec succès',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes en attente', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une commande (pour les employés)
     * 
     * GET /api/orders/{id}/details
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet de voir n'importe quelle commande sans restriction de propriétaire
     * 
     * Retourne tous les détails de la commande :
     * - Informations de la commande
     * - Liste des articles avec détails
     * - Informations du client
     * - Informations de paiement (si existe)
     * - Adresse de livraison (si livraison)
     */
    public function showForEmployee(Request $request, $id)
    {
        try {
            // Récupérer la commande avec toutes les relations
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                ->find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers (les employés ne doivent pas voir les paniers)
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être consultés par les employés',
                ], 400);
            }
            
            // Récupérer les informations de paiement si elles existent
            $payment = Payment::where('id_commande', $commande->id_commande)
                ->orderBy('date_creation', 'desc')
                ->first();
            
            // Formater les articles de la commande
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'prix_unitaire' => $article->prix_unitaire,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => $article->prix_unitaire,
                    'sous_total' => $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Formater les informations de paiement si elles existent
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'statut_payment' => $payment->statut_payment,
                    'methode' => $payment->methode,
                    'montant' => $payment->montant,
                    'transaction_ref' => $payment->transaction_ref,
                    'date_payment' => $payment->date_payment ? $payment->date_payment->format('Y-m-d H:i:s') : null,
                    'date_creation' => $payment->date_creation ? $payment->date_creation->format('Y-m-d H:i:s') : null,
                ];
            }
            
            // Construire la réponse complète avec toutes les informations pour les employés
            return response()->json([
                'success' => true,
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'adresse_livraison' => $commande->adresse_livraison,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'commentaire' => $commande->commentaire,
                        'instructions_speciales' => $commande->instructions_speciales,
                        'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                        'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                        'nb_articles' => $commande->getTotalArticles(),
                        'articles' => $articlesFormates,
                        'client' => [
                            'id_utilisateur' => $commande->utilisateur->id_utilisateur ?? null,
                            'nom' => $commande->utilisateur->nom ?? 'Client supprimé',
                            'prenom' => $commande->utilisateur->prenom ?? '',
                            'email' => $commande->utilisateur->email ?? null,
                            'telephone' => $commande->utilisateur->telephone ?? null,
                        ],
                        'payment' => $paymentInfo,
                    ],
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des détails de commande (employé)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande (pour les employés)
     * 
     * PUT /api/orders/{id}/status
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet de changer le statut d'une commande
     * 
     * Le client peut ensuite consulter la commande via GET /api/orders/{id} pour voir les changements
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        try {
            // Récupérer la commande
            $commande = Commande::find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers (les employés ne doivent pas modifier les paniers)
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être modifiés par les employés',
                ], 400);
            }
            
            // Récupérer le nouveau statut validé
            $nouveauStatut = $request->validated()['statut'];
            
            // Vérifier si le statut change vraiment
            if ($commande->statut === $nouveauStatut) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le statut de la commande est déjà : ' . $this->getStatutLisible($nouveauStatut),
                ], 400);
            }
            
            // Ancien statut pour le logging
            $ancienStatut = $commande->statut;
            
            // Mettre à jour le statut
            // La date_modification sera automatiquement mise à jour grâce à UPDATED_AT = 'date_modification'
            $commande->update(['statut' => $nouveauStatut]);
            
            // Recharger la commande pour avoir les dates à jour
            $commande->refresh();
            
            // Logger le changement de statut
            \Log::info('Statut de commande modifié par un employé', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'employe_id' => $request->user()->id_utilisateur,
                'employe_role' => $request->user()->role,
            ]);

            // Créer une notification pour le client avec un message adapté au statut
            $titreNotification = 'Mise à jour de votre commande';
            $messageNotification = "Le statut de votre commande #{$commande->numero_commande} a été mis à jour: " . $this->getStatutLisible($nouveauStatut);
            
            // Personnaliser le message selon le nouveau statut
            switch ($nouveauStatut) {
                case 'en_preparation':
                    $titreNotification = 'Commande en préparation';
                    $messageNotification = "Votre commande #{$commande->numero_commande} est maintenant en cours de préparation. Elle sera prête bientôt !";
                    break;
                case 'pret':
                    $titreNotification = 'Commande prête';
                    $messageNotification = "Votre commande #{$commande->numero_commande} est prête. Vous pouvez venir la récupérer ou elle sera livrée sous peu !";
                    break;
                case 'livree':
                    $titreNotification = 'Commande livrée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été livrée avec succès. Bon appétit ! 🍽️";
                    break;
                case 'annulee':
                    $titreNotification = 'Commande annulée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été annulée. Pour plus d'informations, contactez-nous.";
                    break;
                case 'confirmee':
                    $titreNotification = 'Commande confirmée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été confirmée. Nous commençons la préparation !";
                    break;
            }
            
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                $titreNotification,
                $messageNotification
            );
            
            // Retourner la réponse avec les informations mises à jour
            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour avec succès',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'statut_lisible' => $this->getStatutLisible($commande->statut),
                        'progression' => $this->calculerProgression($commande->statut),
                        'ancien_statut' => $ancienStatut,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du statut de commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attribuer une commande à un livreur (pour les employés)
     * 
     * PUT /api/orders/{id}/assign
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet d'attribuer une commande de type 'livraison' à un livreur
     */
    public function assign(AssignOrder $request, $id)
    {
        try {
            // Vérifier que l'employé qui fait la demande n'est PAS un livreur
            $employeConnecte = $request->user();
            
            // Vérifier si l'employé connecté est un livreur
            $estLivreurConnecte = DB::table('employe')
                ->where('id_utilisateur', $employeConnecte->id_utilisateur)
                ->where('role_specifique', 'livreur')
                ->exists();
            
            if ($estLivreurConnecte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les livreurs ne peuvent pas attribuer des commandes. Cette fonctionnalité est réservée aux autres employés, gérants et administrateurs.',
                ], 403);
            }
            
            // Récupérer la commande
            $commande = Commande::find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être attribués à un livreur',
                ], 400);
            }
            
            // Vérifier que la commande est de type 'livraison'
            if ($commande->type_commande !== 'livraison') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les commandes de type livraison peuvent être attribuées à un livreur',
                ], 400);
            }
            
            // Récupérer les données validées
            $idLivreur = $request->validated()['id_livreur'];
            
            // Vérifier que l'utilisateur existe et a le rôle 'employe'
            $livreur = Utilisateur::where('id_utilisateur', $idLivreur)
                ->where('role', 'employe')
                ->first();
            
            if (!$livreur) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'utilisateur spécifié n\'est pas un employé',
                ], 400);
            }
            
            // Vérifier que c'est bien un livreur (via la table employe)
            $enregistrementEmploye = DB::table('employe')
                ->where('id_utilisateur', $idLivreur)
                ->first();
            
            if (!$enregistrementEmploye) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé n\'a pas d\'enregistrement dans la table employe. Veuillez créer l\'enregistrement employe via POST /api/admin/employees',
                ], 400);
            }
            
            if ($enregistrementEmploye->role_specifique !== 'livreur') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé n\'est pas un livreur. Son rôle spécifique est : ' . $enregistrementEmploye->role_specifique,
                ], 400);
            }
            
            if ($enregistrementEmploye->statut !== 'actif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce livreur n\'est pas actif. Son statut est : ' . $enregistrementEmploye->statut,
                ], 400);
            }
            
            // Vérifier si la commande a déjà un livreur assigné
            $ancienLivreurId = $commande->id_livreur;
            
            // Attribuer la commande au livreur
            $commande->update(['id_livreur' => $idLivreur]);
            
            // Recharger la commande pour avoir les relations à jour
            $commande->refresh();
            $commande->load('livreur');
            
            // Logger l'attribution
            \Log::info('Commande attribuée à un livreur', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_livreur_id' => $ancienLivreurId,
                'nouveau_livreur_id' => $idLivreur,
                'livreur_nom' => $livreur->nom . ' ' . $livreur->prenom,
                'employe_id' => $request->user()->id_utilisateur,
                'employe_role' => $request->user()->role,
            ]);
            
            // Créer une notification pour le client concernant l'assignation du livreur
            $nomLivreur = trim(($livreur->prenom ?? '') . ' ' . ($livreur->nom ?? ''));
            if (empty($nomLivreur)) {
                $nomLivreur = $livreur->email ?? 'notre livreur';
            }
            
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                'Livreur assigné',
                "Un livreur ({$nomLivreur}) a été assigné à votre commande #{$commande->numero_commande}. Votre commande sera livrée prochainement !"
            );
            
            // Retourner la réponse avec les informations mises à jour
            return response()->json([
                'success' => true,
                'message' => 'Commande attribuée au livreur avec succès',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'type_commande' => $commande->type_commande,
                        'statut' => $commande->statut,
                        'livreur' => $commande->livreur ? [
                            'id_utilisateur' => $commande->livreur->id_utilisateur,
                            'nom' => $commande->livreur->nom,
                            'prenom' => $commande->livreur->prenom,
                            'email' => $commande->livreur->email,
                            'telephone' => $commande->livreur->telephone,
                        ] : null,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'attribution de la commande à un livreur', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'attribution de la commande au livreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique avec tous ses détails
     * 
     * GET /api/orders/{id}
     * 
     * Retourne tous les détails de la commande :
     * - Informations de la commande
     * - Liste des articles avec détails
     * - Informations de paiement (si existe)
     * - Dates importantes
     */
    public function show(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la commande avec toutes les relations
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                ->find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Vérifier que la commande appartient à l'utilisateur connecté (sécurité)
            if ($commande->id_utilisateur !== $utilisateur->id_utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à voir cette commande',
                ], 403);
            }
            
            // Récupérer les informations de paiement si elles existent
            $payment = Payment::where('id_commande', $commande->id_commande)
                ->orderBy('date_creation', 'desc')
                ->first();
            
            // Formater les articles de la commande
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => $article->prix_unitaire,
                    'sous_total' => $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Formater les informations de paiement si elles existent
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'statut_payment' => $payment->statut_payment,
                    'methode' => $payment->methode,
                    'montant' => $payment->montant,
                    'transaction_ref' => $payment->transaction_ref,
                    'date_payment' => $payment->date_payment ? $payment->date_payment->format('Y-m-d H:i:s') : null,
                    'date_creation' => $payment->date_creation ? $payment->date_creation->format('Y-m-d H:i:s') : null,
                ];
            }
            
            // Construire la réponse complète
            return response()->json([
                'success' => true,
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'adresse_livraison' => $commande->adresse_livraison,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'commentaire' => $commande->commentaire,
                        'instructions_speciales' => $commande->instructions_speciales,
                        'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                        'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                        'nb_articles' => $commande->getTotalArticles(),
                        'articles' => $articlesFormates,
                        'payment' => $paymentInfo,
                    ],
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir l'historique complet des commandes du client
     * 
     * GET /api/orders/history
     * 
     * Retourne toutes les commandes du client connecté (hors paniers) triées par date
     * Utile pour afficher l'historique des commandes sur le tableau de bord client
     * 
     * Paramètres optionnels :
     * - status : Filtrer par statut (en_attente, confirmee, en_preparation, pret, livree, annulee)
     * - limit : Nombre de commandes à retourner (pour pagination, défaut: toutes)
     * - offset : Nombre de commandes à sauter (pour pagination)
     */
    public function history(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètres optionnels
            $status = $request->query('status');
            $limit = $request->query('limit');
            $offset = (int) ($request->query('offset', 0));
            
            // Requête de base : toutes les commandes de l'utilisateur sauf les paniers
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->with(['articles.menuItem', 'utilisateur']); // Charger les relations pour les détails
            
            // Filtrer par statut si fourni
            if ($status) {
                $query->where('statut', $status);
            }
            
            // Trier par date de commande (plus récent en premier)
            $query->orderBy('date_commande', 'desc');
            
            // Appliquer la pagination si limit est fourni
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->skip($offset)->take($limit);
            }
            
            $commandes = $query->get();
            
            // Formater la réponse avec tous les détails nécessaires pour l'historique
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le pourcentage de progression pour les commandes actives
                $progression = $this->calculerProgression($commande->statut);
                
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'prix_unitaire' => $article->prix_unitaire,
                        'sous_total' => $article->getSousTotal(),
                    ];
                });
                
                // Construire le nom complet du client
                $nomClient = trim(($commande->utilisateur->prenom ?? '') . ' ' . ($commande->utilisateur->nom ?? ''));
                if (empty($nomClient)) {
                    $nomClient = $commande->utilisateur->email ?? 'Client inconnu';
                }
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'nb_articles' => $commande->getTotalArticles(),
                    'progression' => $progression, // Pourcentage pour les commandes actives
                    'articles' => $articlesFormates, // Détails des articles pour l'affichage
                    'client' => [
                        'nom_complet' => $nomClient,
                        'nom' => $commande->utilisateur->nom ?? '',
                        'prenom' => $commande->utilisateur->prenom ?? '',
                        'email' => $commande->utilisateur->email ?? '',
                    ],
                ];
            });
            
            // Compter le total de commandes (sans pagination) pour les métadonnées
            $totalCommandes = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->when($status, function ($q) use ($status) {
                    return $q->where('statut', $status);
                })
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $totalCommandes,
                    'count' => $commandesFormatees->count(),
                    'offset' => $offset,
                    'has_more' => $limit ? ($offset + $commandesFormatees->count() < $totalCommandes) : false,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'historique des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des commandes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des commandes traitées par jour (pour les employés)
     * 
     * GET /api/stats/employee
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Retourne le nombre de commandes traitées (statut='livree') groupées par jour
     * Utile pour les graphiques et tableaux de bord
     */
    public function employeeStats(Request $request)
    {
        try {
            // Paramètres optionnels pour filtrer la période
            $dateDebut = $request->query('date_debut'); // Format: YYYY-MM-DD
            $dateFin = $request->query('date_fin'); // Format: YYYY-MM-DD
            $nbJours = $request->query('nb_jours', 30); // Nombre de jours par défaut (30 jours)
            
            // Construire la requête de base : commandes traitées (livrées, exclure les paniers)
            $query = Commande::where('statut', 'livree')
                ->where('statut', '!=', 'panier');
            
            // Si aucune date spécifiée, prendre les 30 derniers jours par défaut
            if (!$dateDebut && !$dateFin) {
                $dateDebut = now()->subDays($nbJours)->format('Y-m-d');
                $dateFin = now()->format('Y-m-d');
            }
            
            // Appliquer les filtres de date si fournis
            if ($dateDebut) {
                $query->whereDate('date_modification', '>=', $dateDebut);
            }
            
            if ($dateFin) {
                $query->whereDate('date_modification', '<=', $dateFin);
            }
            
            // Grouper par jour et compter
            // Utiliser date_modification car c'est la date où la commande a été marquée comme livrée
            $statsParJour = $query->select(
                DB::raw('DATE(date_modification) as jour'),
                DB::raw('COUNT(*) as nombre_commandes'),
                DB::raw('SUM(montant_total) as montant_total')
            )
            ->groupBy(DB::raw('DATE(date_modification)'))
            ->orderBy('jour', 'desc')
            ->get();
            
            // Formater les données pour faciliter l'affichage dans un graphique
            $statsFormatees = $statsParJour->map(function ($stat) {
                return [
                    'date' => $stat->jour,
                    'nombre_commandes' => (int) $stat->nombre_commandes,
                    'montant_total' => (float) $stat->montant_total,
                ];
            });
            
            // Calculer les totaux pour la période
            $totalCommandes = $statsParJour->sum('nombre_commandes');
            $totalMontant = $statsParJour->sum('montant_total');
            
            // Calculer le nombre réel de jours dans la période
            $nbJoursPeriode = $statsParJour->count();
            
            // Statistiques supplémentaires
            $moyenneParJour = $nbJoursPeriode > 0 
                ? round($totalCommandes / $nbJoursPeriode, 2) 
                : 0;
            
            // Trouver le jour avec le maximum et le minimum de commandes
            $jourMax = $statsParJour->count() > 0 
                ? (int) $statsParJour->max('nombre_commandes') 
                : 0;
            $jourMin = $statsParJour->count() > 0 
                ? (int) $statsParJour->min('nombre_commandes') 
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'stats_par_jour' => $statsFormatees,
                    'totaux' => [
                        'total_commandes' => (int) $totalCommandes,
                        'total_montant' => (float) $totalMontant,
                        'moyenne_par_jour' => $moyenneParJour,
                        'max_commandes_jour' => $jourMax,
                        'min_commandes_jour' => $jourMin,
                    ],
                    'periode' => [
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'nb_jours_avec_commandes' => $nbJoursPeriode,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traiter le paiement d'une commande via EasyPay
     * 
     * POST /api/orders/{id}/payment
     */
    public function processPayment(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $utilisateur = $request->user();
            $data = $request->all(); // Utiliser all() au lieu de validated()

            // Récupérer la commande
            $commande = Commande::where('id_commande', $id)
                               ->where('id_utilisateur', $utilisateur->id_utilisateur)
                               ->first();

            if (!$commande) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }

            if ($commande->statut !== 'en_attente') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut pas être payée',
                ], 400);
            }

            // Convertir mode_paiement en format attendu par PaymentController
            $paymentMethod = 'mobile_money';
            if (isset($data['mode_paiement'])) {
                $paymentMethod = $data['mode_paiement'] === 'carte_bancaire' ? 'credit_card' : 'mobile_money';
            }

            // Préparer les données pour le paiement
            $paymentRequestData = [
                'commande_id' => $commande->id_commande,
                'payment_method' => $paymentMethod,
                'language' => 'fr',
            ];

            // Valider les données
            $validator = \Validator::make($paymentRequestData, [
                'commande_id' => 'required|integer|exists:commandes,id_commande',
                'language' => 'nullable|in:fr,en',
                'payment_method' => 'nullable|in:credit_card,mobile_money',
            ]);
            
            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Données de paiement invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Vérifier que la commande n'est pas déjà payée
            $existingPayment = Payment::where('id_commande', $commande->id_commande)
                ->where('statut_payment', 'paye')
                ->first();

            if ($existingPayment) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est déjà payée',
                ], 400);
            }

            // Calculer le montant total
            $montantTotal = $commande->montant_total ?? $commande->getTotal();

            // Déterminer les channels Easypay selon le choix
            $channels = [];
            $methodePayment = 'mobile_money';
            
            if ($paymentMethod === 'credit_card') {
                $channels = [['channel' => 'CREDIT CARD']];
                $methodePayment = 'carte_bancaire';
            } else {
                $channels = [['channel' => 'MOBILE MONEY']];
                $methodePayment = 'mobile_money';
            }

            // Préparer les données pour Easypay
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $backendUrl = config('app.url', 'http://localhost:8000');
            
            // Charger la relation utilisateur
            $commande->load('utilisateur');
            
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
                'language' => 'fr',
                'channels' => $channels,
            ];

            // Appeler EasypayService directement
            $easypayService = app(EasypayService::class);
            $result = $easypayService->initializeTransaction($easypayData);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                ], 500);
            }

            // Enregistrer le paiement en base de données
            $payment = Payment::create([
                'id_commande' => $commande->id_commande,
                'montant' => $montantTotal,
                'methode' => $methodePayment,
                'statut_payment' => 'en_attente',
                'transaction_ref' => $result['reference'],
            ]);

            // Envoyer l'email avec le réçu PDF lors de la redirection vers EasyPay
            try {
                \Log::info('Envoi de l\'email avec réçu PDF lors de l\'initialisation du paiement EasyPay', [
                    'payment_id' => $payment->id_payment,
                    'commande_id' => $commande->id_commande,
                    'email' => $commande->utilisateur->email
                ]);
                
                Mail::to($commande->utilisateur->email)->send(new PaymentReceiptMail($payment));
                
                \Log::info('Email avec réçu PDF envoyé avec succès lors de l\'initialisation du paiement', [
                    'payment_id' => $payment->id_payment,
                    'email' => $commande->utilisateur->email
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais ne fait pas échouer l'initialisation du paiement
                \Log::error('Erreur lors de l\'envoi du réçu de paiement lors de l\'initialisation', [
                    'payment_id' => $payment->id_payment,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            DB::commit();

            // Retourner la réponse
            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès',
                'data' => [
                    'payment_id' => $payment->id_payment ?? null,
                    'reference' => $result['reference'],
                    'redirect_url' => $result['redirect_url'],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors du traitement du paiement', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une commande de l'historique (soft delete ou hard delete selon les règles métier)
     * 
     * DELETE /api/orders/{id}
     * 
     * L'utilisateur ne peut supprimer que ses propres commandes
     * Les commandes avec certains statuts peuvent ne pas être supprimables
     */
    public function destroy(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la commande
            $commande = Commande::where('id_commande', $id)
                ->where('id_utilisateur', $utilisateur->id_utilisateur)
                ->first();
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable ou vous n\'avez pas le droit de la supprimer',
                ], 404);
            }
            
            // Vérifier si la commande peut être supprimée
            // Les commandes en cours ou livrées ne peuvent généralement pas être supprimées
            $statutsNonSupprimables = ['en_preparation', 'pret', 'livree'];
            if (in_array($commande->statut, $statutsNonSupprimables)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une commande avec le statut "' . $this->getStatutLisible($commande->statut) . '"',
                ], 400);
            }
            
            // Supprimer les articles associés
            $commande->articles()->delete();
            
            // Supprimer la commande
            $commande->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès',
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la commande',
                'error' => $e->getMessage(),
            ], 500);
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
            \Log::error('Erreur lors de la création de la notification', [
                'user_id' => $idUtilisateur,
                'commande_id' => $idCommande,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifier tous les employés d'une nouvelle commande
     * 
     * @param Commande $commande
     * @return void
     */
    private function notifyEmployeesOfNewOrder(Commande $commande)
    {
        try {
            // Récupérer tous les employés actifs (employe, gerant, admin)
            $employes = Utilisateur::whereIn('role', ['employe', 'gerant', 'admin'])
                ->where('statut_compte', 'actif')
                ->get();

            $nombreNotifies = 0;

            foreach ($employes as $employe) {
                try {
                    // Créer une notification pour chaque employé
                    $this->createNotification(
                        $employe->id_utilisateur,
                        $commande->id_commande,
                        'commande',
                        'Nouvelle commande',
                        "Nouvelle commande #{$commande->numero_commande} de {$commande->utilisateur->nom} {$commande->utilisateur->prenom}. Montant: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF"
                    );
                    $nombreNotifies++;
                } catch (\Exception $e) {
                    // Log l'erreur pour cet employé mais continue avec les autres
                    \Log::error('Erreur lors de la notification d\'un employé', [
                        'employe_id' => $employe->id_utilisateur,
                        'commande_id' => $commande->id_commande,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            \Log::info('Notifications employés envoyées', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'employes_notifies' => $nombreNotifies,
                'total_employes' => $employes->count(),
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            \Log::error('Erreur lors de la notification des employés', [
                'commande_id' => $commande->id_commande ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gérer le parrainage lors de la première commande du filleul
     * 
     * @param Commande $commande
     * @param Utilisateur $utilisateur
     * @return void
     */
    private function handlePremiereCommandeParrainage(Commande $commande, Utilisateur $utilisateur)
    {
        try {
            // Vérifier si c'est la première commande de l'utilisateur
            $nombreCommandes = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->where('id_commande', '!=', $commande->id_commande)
                ->count();
            
            // Si ce n'est pas la première commande, ne rien faire
            if ($nombreCommandes > 0) {
                return;
            }
            
            // Vérifier si l'utilisateur a un parrain
            if (!$utilisateur->parrain_id) {
                return;
            }
            
            // Récupérer le parrainage
            $parrainage = Parrainage::where('id_filleul', $utilisateur->id_utilisateur)
                ->where('premiere_commande_faite', false)
                ->first();
            
            // Si le parrainage n'existe pas ou si les points ont déjà été attribués, ne rien faire
            if (!$parrainage) {
                return;
            }
            
            // Récupérer les points de première commande depuis parametres_fidelite
            $parametresFidelite = DB::table('parametres_fidelite')
                ->where('actif', true)
                ->orderBy('date_debut_application', 'desc')
                ->first();
            
            $pointsPremiereCommande = $parametresFidelite ? $parametresFidelite->points_premiere_commande : 20; // Default 20 si pas trouvé
            
            // Récupérer le parrain
            $parrain = Utilisateur::find($parrainage->id_parrain);
            
            if (!$parrain) {
                \Log::error('Parrain non trouvé pour le parrainage', [
                    'parrainage_id' => $parrainage->id_parrainage,
                    'parrain_id' => $parrainage->id_parrain,
                ]);
                return;
            }
            
            // Attribuer les points au parrain
            $parrain->increment('points_balance', $pointsPremiereCommande);
            
            // Mettre à jour le parrainage
            $parrainage->update([
                'premiere_commande_faite' => true,
                'date_premiere_commande' => now(),
                'points_premiere_commande' => $pointsPremiereCommande,
            ]);
            
            // Envoyer une notification au parrain
            Notification::create([
                'id_utilisateur' => $parrain->id_utilisateur,
                'id_commande' => $commande->id_commande,
                'type_notification' => 'system',
                'titre' => 'Première commande de votre filleul',
                'message' => "{$utilisateur->prenom} {$utilisateur->nom} a effectué sa première commande #{$commande->numero_commande}. Vous avez gagné {$pointsPremiereCommande} points supplémentaires !",
                'lu' => false,
                'date_creation' => now(),
            ]);
            
            \Log::info('Points de première commande attribués au parrain', [
                'parrain_id' => $parrain->id_utilisateur,
                'filleul_id' => $utilisateur->id_utilisateur,
                'commande_id' => $commande->id_commande,
                'points_attribues' => $pointsPremiereCommande,
            ]);
            
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            \Log::error('Erreur lors de la gestion du parrainage pour la première commande', [
                'commande_id' => $commande->id_commande ?? null,
                'utilisateur_id' => $utilisateur->id_utilisateur ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

     * 
     * Paramètres optionnels :
     * - hours : Nombre d'heures en arrière pour chercher les mises à jour (défaut: 24)
     * - status : Filtrer par statut spécifique
     * 
     * Utile pour :
     * - Afficher des notifications "Nouvelle mise à jour de votre commande"
     * - Détecter les changements de statut récents
     * - Alerter l'utilisateur des commandes qui ont changé
     */
    public function recentUpdates(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètre optionnel : nombre d'heures (défaut: 24 heures)
            $hours = (int) ($request->query('hours', 24));
            // Limiter entre 1 heure et 168 heures (7 jours) pour éviter les abus
            $hours = max(1, min(168, $hours));
            
            // Paramètre optionnel de filtrage par statut
            $status = $request->query('status');
            
            // Date limite pour les mises à jour récentes
            $dateLimite = now()->subHours($hours);
            
            // Requête de base : commandes modifiées récemment (hors paniers)
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->where('date_modification', '>=', $dateLimite)
                ->with(['articles.menuItem']); // Charger les relations
            
            // Filtrer par statut si fourni
            if ($status) {
                $query->where('statut', $status);
            }
            
            // Trier par date de modification (plus récent en premier)
            $commandes = $query->orderBy('date_modification', 'desc')
                ->orderBy('date_commande', 'desc')
                ->get();
            
            // Formater la réponse avec des informations de notification
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le temps écoulé depuis la dernière modification
                $tempsEcoule = $commande->date_modification->diffForHumans();
                
                // Calculer le pourcentage de progression
                $progression = $this->calculerProgression($commande->statut);
                
                // Déterminer si c'est une nouvelle mise à jour (moins de 1 heure)
                $isNouvelleMiseAJour = $commande->date_modification->isAfter(now()->subHour());
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                    'temps_ecoule' => $tempsEcoule, // "il y a 2 heures", "il y a 5 minutes"
                    'progression' => $progression,
                    'nb_articles' => $commande->getTotalArticles(),
                    'is_nouvelle_mise_a_jour' => $isNouvelleMiseAJour, // Pour affichage badge "Nouveau"
                    'adresse_livraison' => $commande->adresse_livraison,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $commandesFormatees->count(),
                    'periode_heures' => $hours,
                    'date_limite' => $dateLimite->format('Y-m-d H:i:s'),
                    'nouvelles_mises_a_jour' => $commandesFormatees->where('is_nouvelle_mise_a_jour', true)->count(),
                    'message' => 'Commandes récemment mises à jour récupérées avec succès',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes récemment mises à jour', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes récemment mises à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $utilisateur = $request->user();
            $data = $request->validated();

            // Récupérer le panier actif de l'utilisateur
            $panier = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                              ->where('statut', 'panier')
                              ->with('articles')
                              ->first();

            if (!$panier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide',
                ], 404);
            }

            // Vérifier que le panier contient des articles
            if ($panier->articles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide. Ajoutez des articles avant de passer commande.',
                ], 400);
            }

            // Calculer le montant total du panier
            $montantTotal = $panier->getTotal();
            $pointsUtilises = $data['points_utilises'] ?? 0;
            $reductionPoints = 0;
            $fraisLivraison = 0;

            // Calculer les frais de livraison si c'est une livraison
            if ($data['type_commande'] === 'livraison') {
                $fraisLivraison = 2000; // 2000 FC (Franc Congolais) pour la livraison
            }

            // Gérer les points de fidélité si utilisés
            if ($pointsUtilises > 0) {
                // Vérifier que l'utilisateur a assez de points
                if ($utilisateur->points_balance < $pointsUtilises) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas assez de points. Votre solde: ' . $utilisateur->points_balance,
                    ], 400);
                }

                // Calculer la réduction (15 points = 1000 FC)
                $valeurPoint = 1000; // 1 point = 1000/15 ≈ 66.67 FC (Franc Congolais)
                $reductionPoints = ($pointsUtilises * $valeurPoint) / 15;

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

            // Déduire les points utilisés du solde de l'utilisateur
            if ($pointsUtilises > 0) {
                $utilisateur->points_balance -= $pointsUtilises;
                $utilisateur->save();
            }

            // Mettre à jour le panier en commande
            $panier->update([
                'statut' => 'en_attente',
                'type_commande' => $data['type_commande'],
                'adresse_livraison' => $data['adresse_livraison'] ?? null,
                'points_utilises' => $pointsUtilises,
                'reduction_points' => $reductionPoints,
                'frais_livraison' => $fraisLivraison,
                'montant_total' => $montantFinal,
                'commentaire' => $data['commentaire'] ?? null,
                'instructions_speciales' => $data['instructions_speciales'] ?? null,
                'heure_arrivee_prevue' => $data['heure_arrivee_prevue'] ?? null,
                'date_modification' => now(),
            ]);

            // Le trigger va générer automatiquement le numero_commande

            DB::commit();

            // Charger les relations pour la réponse et l'email
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                                ->find($panier->id_commande);

            // Vérifier si c'est la première commande et gérer le parrainage
            $this->handlePremiereCommandeParrainage($commande, $utilisateur);

            // Envoyer l'email de confirmation
            try {
                Mail::to($commande->utilisateur->email)->send(new CommandeConfirmationMail($commande));
            } catch (\Exception $e) {
                // Log l'erreur mais ne fait pas échouer la commande
                \Log::error('Erreur lors de l\'envoi de l\'email de confirmation', [
                    'commande_id' => $commande->id_commande,
                    'error' => $e->getMessage(),
                ]);
            }

            // Créer une notification pour le client
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                'Commande passée',
                "Votre commande #{$commande->numero_commande} a été passée avec succès. Montant total: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF"
            );

            // Notifier tous les employés de la nouvelle commande
            $this->notifyEmployeesOfNewOrder($commande);

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès !',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'date_commande' => $commande->date_commande,
                        'articles' => $commande->articles->map(function ($article) {
                            return [
                                'id' => $article->id_commande_article,
                                'plat' => $article->menuItem->nom ?? 'Plat supprimé',
                                'quantite' => $article->quantite,
                                'prix_unitaire' => $article->prix_unitaire,
                                'sous_total' => $article->getSousTotal(),
                            ];
                        }),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la création de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Lister les commandes en attente (pour les employés)
     * 
     * GET /api/orders/pending
     * 
     * Retourne toutes les commandes avec le statut "en_attente"
     * Accessible uniquement aux employés (employe, gerant, admin)
     */
    public function pending(Request $request)
    {
        try {
            // Récupérer toutes les commandes en attente (hors paniers)
            $commandes = Commande::where('statut', 'en_attente')
                ->with(['articles.menuItem', 'utilisateur']) // Charger les relations
                ->orderBy('date_commande', 'asc') // Plus anciennes en premier (FIFO)
                ->get();
            
            // Formater la réponse avec tous les détails nécessaires pour les employés
            $commandesFormatees = $commandes->map(function ($commande) {
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'description' => $article->menuItem->description ?? null,
                            'prix_unitaire' => $article->prix_unitaire,
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'sous_total' => $article->getSousTotal(),
                        'instructions' => $article->instructions ?? null,
                    ];
                });

                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'commentaire' => $commande->commentaire,
                    'instructions_speciales' => $commande->instructions_speciales,
                    'nb_articles' => $commande->getTotalArticles(),
                    'client' => [
                        'id_utilisateur' => $commande->utilisateur->id_utilisateur ?? null,
                        'nom' => $commande->utilisateur->nom ?? 'Client supprimé',
                        'prenom' => $commande->utilisateur->prenom ?? '',
                        'email' => $commande->utilisateur->email ?? null,
                        'telephone' => $commande->utilisateur->telephone ?? null,
                    ],
                    'articles' => $articlesFormates,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $commandesFormatees->count(),
                    'message' => 'Commandes en attente récupérées avec succès',
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes en attente', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en attente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une commande (pour les employés)
     * 
     * GET /api/orders/{id}/details
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet de voir n'importe quelle commande sans restriction de propriétaire
     * 
     * Retourne tous les détails de la commande :
     * - Informations de la commande
     * - Liste des articles avec détails
     * - Informations du client
     * - Informations de paiement (si existe)
     * - Adresse de livraison (si livraison)
     */
    public function showForEmployee(Request $request, $id)
    {
        try {
            // Récupérer la commande avec toutes les relations
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                ->find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers (les employés ne doivent pas voir les paniers)
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être consultés par les employés',
                ], 400);
            }
            
            // Récupérer les informations de paiement si elles existent
            $payment = Payment::where('id_commande', $commande->id_commande)
                ->orderBy('date_creation', 'desc')
                ->first();
            
            // Formater les articles de la commande
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'prix_unitaire' => $article->prix_unitaire,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => $article->prix_unitaire,
                    'sous_total' => $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Formater les informations de paiement si elles existent
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'statut_payment' => $payment->statut_payment,
                    'methode' => $payment->methode,
                    'montant' => $payment->montant,
                    'transaction_ref' => $payment->transaction_ref,
                    'date_payment' => $payment->date_payment ? $payment->date_payment->format('Y-m-d H:i:s') : null,
                    'date_creation' => $payment->date_creation ? $payment->date_creation->format('Y-m-d H:i:s') : null,
                ];
            }
            
            // Construire la réponse complète avec toutes les informations pour les employés
            return response()->json([
                'success' => true,
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'adresse_livraison' => $commande->adresse_livraison,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'commentaire' => $commande->commentaire,
                        'instructions_speciales' => $commande->instructions_speciales,
                        'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                        'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                        'nb_articles' => $commande->getTotalArticles(),
                        'articles' => $articlesFormates,
                        'client' => [
                            'id_utilisateur' => $commande->utilisateur->id_utilisateur ?? null,
                            'nom' => $commande->utilisateur->nom ?? 'Client supprimé',
                            'prenom' => $commande->utilisateur->prenom ?? '',
                            'email' => $commande->utilisateur->email ?? null,
                            'telephone' => $commande->utilisateur->telephone ?? null,
                        ],
                        'payment' => $paymentInfo,
                    ],
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des détails de commande (employé)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande (pour les employés)
     * 
     * PUT /api/orders/{id}/status
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet de changer le statut d'une commande
     * 
     * Le client peut ensuite consulter la commande via GET /api/orders/{id} pour voir les changements
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        try {
            // Récupérer la commande
            $commande = Commande::find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers (les employés ne doivent pas modifier les paniers)
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être modifiés par les employés',
                ], 400);
            }
            
            // Récupérer le nouveau statut validé
            $nouveauStatut = $request->validated()['statut'];
            
            // Vérifier si le statut change vraiment
            if ($commande->statut === $nouveauStatut) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le statut de la commande est déjà : ' . $this->getStatutLisible($nouveauStatut),
                ], 400);
            }
            
            // Ancien statut pour le logging
            $ancienStatut = $commande->statut;
            
            // Mettre à jour le statut
            // La date_modification sera automatiquement mise à jour grâce à UPDATED_AT = 'date_modification'
            $commande->update(['statut' => $nouveauStatut]);
            
            // Recharger la commande pour avoir les dates à jour
            $commande->refresh();
            
            // Logger le changement de statut
            \Log::info('Statut de commande modifié par un employé', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'employe_id' => $request->user()->id_utilisateur,
                'employe_role' => $request->user()->role,
            ]);

            // Créer une notification pour le client avec un message adapté au statut
            $titreNotification = 'Mise à jour de votre commande';
            $messageNotification = "Le statut de votre commande #{$commande->numero_commande} a été mis à jour: " . $this->getStatutLisible($nouveauStatut);
            
            // Personnaliser le message selon le nouveau statut
            switch ($nouveauStatut) {
                case 'en_preparation':
                    $titreNotification = 'Commande en préparation';
                    $messageNotification = "Votre commande #{$commande->numero_commande} est maintenant en cours de préparation. Elle sera prête bientôt !";
                    break;
                case 'pret':
                    $titreNotification = 'Commande prête';
                    $messageNotification = "Votre commande #{$commande->numero_commande} est prête. Vous pouvez venir la récupérer ou elle sera livrée sous peu !";
                    break;
                case 'livree':
                    $titreNotification = 'Commande livrée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été livrée avec succès. Bon appétit ! 🍽️";
                    break;
                case 'annulee':
                    $titreNotification = 'Commande annulée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été annulée. Pour plus d'informations, contactez-nous.";
                    break;
                case 'confirmee':
                    $titreNotification = 'Commande confirmée';
                    $messageNotification = "Votre commande #{$commande->numero_commande} a été confirmée. Nous commençons la préparation !";
                    break;
            }
            
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                $titreNotification,
                $messageNotification
            );
            
            // Retourner la réponse avec les informations mises à jour
            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour avec succès',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'statut_lisible' => $this->getStatutLisible($commande->statut),
                        'progression' => $this->calculerProgression($commande->statut),
                        'ancien_statut' => $ancienStatut,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du statut de commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attribuer une commande à un livreur (pour les employés)
     * 
     * PUT /api/orders/{id}/assign
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Permet d'attribuer une commande de type 'livraison' à un livreur
     */
    public function assign(AssignOrder $request, $id)
    {
        try {
            // Vérifier que l'employé qui fait la demande n'est PAS un livreur
            $employeConnecte = $request->user();
            
            // Vérifier si l'employé connecté est un livreur
            $estLivreurConnecte = DB::table('employe')
                ->where('id_utilisateur', $employeConnecte->id_utilisateur)
                ->where('role_specifique', 'livreur')
                ->exists();
            
            if ($estLivreurConnecte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les livreurs ne peuvent pas attribuer des commandes. Cette fonctionnalité est réservée aux autres employés, gérants et administrateurs.',
                ], 403);
            }
            
            // Récupérer la commande
            $commande = Commande::find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Exclure les paniers
            if ($commande->statut === 'panier') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les paniers ne peuvent pas être attribués à un livreur',
                ], 400);
            }
            
            // Vérifier que la commande est de type 'livraison'
            if ($commande->type_commande !== 'livraison') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les commandes de type livraison peuvent être attribuées à un livreur',
                ], 400);
            }
            
            // Récupérer les données validées
            $idLivreur = $request->validated()['id_livreur'];
            
            // Vérifier que l'utilisateur existe et a le rôle 'employe'
            $livreur = Utilisateur::where('id_utilisateur', $idLivreur)
                ->where('role', 'employe')
                ->first();
            
            if (!$livreur) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'utilisateur spécifié n\'est pas un employé',
                ], 400);
            }
            
            // Vérifier que c'est bien un livreur (via la table employe)
            $enregistrementEmploye = DB::table('employe')
                ->where('id_utilisateur', $idLivreur)
                ->first();
            
            if (!$enregistrementEmploye) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé n\'a pas d\'enregistrement dans la table employe. Veuillez créer l\'enregistrement employe via POST /api/admin/employees',
                ], 400);
            }
            
            if ($enregistrementEmploye->role_specifique !== 'livreur') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet employé n\'est pas un livreur. Son rôle spécifique est : ' . $enregistrementEmploye->role_specifique,
                ], 400);
            }
            
            if ($enregistrementEmploye->statut !== 'actif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce livreur n\'est pas actif. Son statut est : ' . $enregistrementEmploye->statut,
                ], 400);
            }
            
            // Vérifier si la commande a déjà un livreur assigné
            $ancienLivreurId = $commande->id_livreur;
            
            // Attribuer la commande au livreur
            $commande->update(['id_livreur' => $idLivreur]);
            
            // Recharger la commande pour avoir les relations à jour
            $commande->refresh();
            $commande->load('livreur');
            
            // Logger l'attribution
            \Log::info('Commande attribuée à un livreur', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_livreur_id' => $ancienLivreurId,
                'nouveau_livreur_id' => $idLivreur,
                'livreur_nom' => $livreur->nom . ' ' . $livreur->prenom,
                'employe_id' => $request->user()->id_utilisateur,
                'employe_role' => $request->user()->role,
            ]);
            
            // Créer une notification pour le client concernant l'assignation du livreur
            $nomLivreur = trim(($livreur->prenom ?? '') . ' ' . ($livreur->nom ?? ''));
            if (empty($nomLivreur)) {
                $nomLivreur = $livreur->email ?? 'notre livreur';
            }
            
            $this->createNotification(
                $commande->id_utilisateur,
                $commande->id_commande,
                'commande',
                'Livreur assigné',
                "Un livreur ({$nomLivreur}) a été assigné à votre commande #{$commande->numero_commande}. Votre commande sera livrée prochainement !"
            );
            
            // Retourner la réponse avec les informations mises à jour
            return response()->json([
                'success' => true,
                'message' => 'Commande attribuée au livreur avec succès',
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'type_commande' => $commande->type_commande,
                        'statut' => $commande->statut,
                        'livreur' => $commande->livreur ? [
                            'id_utilisateur' => $commande->livreur->id_utilisateur,
                            'nom' => $commande->livreur->nom,
                            'prenom' => $commande->livreur->prenom,
                            'email' => $commande->livreur->email,
                            'telephone' => $commande->livreur->telephone,
                        ] : null,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'attribution de la commande à un livreur', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'attribution de la commande au livreur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique avec tous ses détails
     * 
     * GET /api/orders/{id}
     * 
     * Retourne tous les détails de la commande :
     * - Informations de la commande
     * - Liste des articles avec détails
     * - Informations de paiement (si existe)
     * - Dates importantes
     */
    public function show(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la commande avec toutes les relations
            $commande = Commande::with(['articles.menuItem', 'utilisateur'])
                ->find($id);
            
            // Vérifier que la commande existe
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }
            
            // Vérifier que la commande appartient à l'utilisateur connecté (sécurité)
            if ($commande->id_utilisateur !== $utilisateur->id_utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à voir cette commande',
                ], 403);
            }
            
            // Récupérer les informations de paiement si elles existent
            $payment = Payment::where('id_commande', $commande->id_commande)
                ->orderBy('date_creation', 'desc')
                ->first();
            
            // Formater les articles de la commande
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => $article->prix_unitaire,
                    'sous_total' => $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Formater les informations de paiement si elles existent
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'statut_payment' => $payment->statut_payment,
                    'methode' => $payment->methode,
                    'montant' => $payment->montant,
                    'transaction_ref' => $payment->transaction_ref,
                    'date_payment' => $payment->date_payment ? $payment->date_payment->format('Y-m-d H:i:s') : null,
                    'date_creation' => $payment->date_creation ? $payment->date_creation->format('Y-m-d H:i:s') : null,
                ];
            }
            
            // Construire la réponse complète
            return response()->json([
                'success' => true,
                'data' => [
                    'commande' => [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'type_commande' => $commande->type_commande,
                        'adresse_livraison' => $commande->adresse_livraison,
                        'montant_total' => $commande->montant_total,
                        'frais_livraison' => $commande->frais_livraison,
                        'points_utilises' => $commande->points_utilises,
                        'reduction_points' => $commande->reduction_points,
                        'commentaire' => $commande->commentaire,
                        'instructions_speciales' => $commande->instructions_speciales,
                        'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                        'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                        'nb_articles' => $commande->getTotalArticles(),
                        'articles' => $articlesFormates,
                        'payment' => $paymentInfo,
                    ],
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir l'historique complet des commandes du client
     * 
     * GET /api/orders/history
     * 
     * Retourne toutes les commandes du client connecté (hors paniers) triées par date
     * Utile pour afficher l'historique des commandes sur le tableau de bord client
     * 
     * Paramètres optionnels :
     * - status : Filtrer par statut (en_attente, confirmee, en_preparation, pret, livree, annulee)
     * - limit : Nombre de commandes à retourner (pour pagination, défaut: toutes)
     * - offset : Nombre de commandes à sauter (pour pagination)
     */
    public function history(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Paramètres optionnels
            $status = $request->query('status');
            $limit = $request->query('limit');
            $offset = (int) ($request->query('offset', 0));
            
            // Requête de base : toutes les commandes de l'utilisateur sauf les paniers
            $query = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->with(['articles.menuItem', 'utilisateur']); // Charger les relations pour les détails
            
            // Filtrer par statut si fourni
            if ($status) {
                $query->where('statut', $status);
            }
            
            // Trier par date de commande (plus récent en premier)
            $query->orderBy('date_commande', 'desc');
            
            // Appliquer la pagination si limit est fourni
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->skip($offset)->take($limit);
            }
            
            $commandes = $query->get();
            
            // Formater la réponse avec tous les détails nécessaires pour l'historique
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le pourcentage de progression pour les commandes actives
                $progression = $this->calculerProgression($commande->statut);
                
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'prix_unitaire' => $article->prix_unitaire,
                        'sous_total' => $article->getSousTotal(),
                    ];
                });
                
                // Construire le nom complet du client
                $nomClient = trim(($commande->utilisateur->prenom ?? '') . ' ' . ($commande->utilisateur->nom ?? ''));
                if (empty($nomClient)) {
                    $nomClient = $commande->utilisateur->email ?? 'Client inconnu';
                }
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total,
                    'frais_livraison' => $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') : null,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'nb_articles' => $commande->getTotalArticles(),
                    'progression' => $progression, // Pourcentage pour les commandes actives
                    'articles' => $articlesFormates, // Détails des articles pour l'affichage
                    'client' => [
                        'nom_complet' => $nomClient,
                        'nom' => $commande->utilisateur->nom ?? '',
                        'prenom' => $commande->utilisateur->prenom ?? '',
                        'email' => $commande->utilisateur->email ?? '',
                    ],
                ];
            });
            
            // Compter le total de commandes (sans pagination) pour les métadonnées
            $totalCommandes = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->when($status, function ($q) use ($status) {
                    return $q->where('statut', $status);
                })
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'total' => $totalCommandes,
                    'count' => $commandesFormatees->count(),
                    'offset' => $offset,
                    'has_more' => $limit ? ($offset + $commandesFormatees->count() < $totalCommandes) : false,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'historique des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des commandes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des commandes traitées par jour (pour les employés)
     * 
     * GET /api/stats/employee
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Retourne le nombre de commandes traitées (statut='livree') groupées par jour
     * Utile pour les graphiques et tableaux de bord
     */
    public function employeeStats(Request $request)
    {
        try {
            // Paramètres optionnels pour filtrer la période
            $dateDebut = $request->query('date_debut'); // Format: YYYY-MM-DD
            $dateFin = $request->query('date_fin'); // Format: YYYY-MM-DD
            $nbJours = $request->query('nb_jours', 30); // Nombre de jours par défaut (30 jours)
            
            // Construire la requête de base : commandes traitées (livrées, exclure les paniers)
            $query = Commande::where('statut', 'livree')
                ->where('statut', '!=', 'panier');
            
            // Si aucune date spécifiée, prendre les 30 derniers jours par défaut
            if (!$dateDebut && !$dateFin) {
                $dateDebut = now()->subDays($nbJours)->format('Y-m-d');
                $dateFin = now()->format('Y-m-d');
            }
            
            // Appliquer les filtres de date si fournis
            if ($dateDebut) {
                $query->whereDate('date_modification', '>=', $dateDebut);
            }
            
            if ($dateFin) {
                $query->whereDate('date_modification', '<=', $dateFin);
            }
            
            // Grouper par jour et compter
            // Utiliser date_modification car c'est la date où la commande a été marquée comme livrée
            $statsParJour = $query->select(
                DB::raw('DATE(date_modification) as jour'),
                DB::raw('COUNT(*) as nombre_commandes'),
                DB::raw('SUM(montant_total) as montant_total')
            )
            ->groupBy(DB::raw('DATE(date_modification)'))
            ->orderBy('jour', 'desc')
            ->get();
            
            // Formater les données pour faciliter l'affichage dans un graphique
            $statsFormatees = $statsParJour->map(function ($stat) {
                return [
                    'date' => $stat->jour,
                    'nombre_commandes' => (int) $stat->nombre_commandes,
                    'montant_total' => (float) $stat->montant_total,
                ];
            });
            
            // Calculer les totaux pour la période
            $totalCommandes = $statsParJour->sum('nombre_commandes');
            $totalMontant = $statsParJour->sum('montant_total');
            
            // Calculer le nombre réel de jours dans la période
            $nbJoursPeriode = $statsParJour->count();
            
            // Statistiques supplémentaires
            $moyenneParJour = $nbJoursPeriode > 0 
                ? round($totalCommandes / $nbJoursPeriode, 2) 
                : 0;
            
            // Trouver le jour avec le maximum et le minimum de commandes
            $jourMax = $statsParJour->count() > 0 
                ? (int) $statsParJour->max('nombre_commandes') 
                : 0;
            $jourMin = $statsParJour->count() > 0 
                ? (int) $statsParJour->min('nombre_commandes') 
                : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'stats_par_jour' => $statsFormatees,
                    'totaux' => [
                        'total_commandes' => (int) $totalCommandes,
                        'total_montant' => (float) $totalMontant,
                        'moyenne_par_jour' => $moyenneParJour,
                        'max_commandes_jour' => $jourMax,
                        'min_commandes_jour' => $jourMin,
                    ],
                    'periode' => [
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'nb_jours_avec_commandes' => $nbJoursPeriode,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traiter le paiement d'une commande via EasyPay
     * 
     * POST /api/orders/{id}/payment
     */
    public function processPayment(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $utilisateur = $request->user();
            $data = $request->all(); // Utiliser all() au lieu de validated()

            // Récupérer la commande
            $commande = Commande::where('id_commande', $id)
                               ->where('id_utilisateur', $utilisateur->id_utilisateur)
                               ->first();

            if (!$commande) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }

            if ($commande->statut !== 'en_attente') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut pas être payée',
                ], 400);
            }

            // Convertir mode_paiement en format attendu par PaymentController
            $paymentMethod = 'mobile_money';
            if (isset($data['mode_paiement'])) {
                $paymentMethod = $data['mode_paiement'] === 'carte_bancaire' ? 'credit_card' : 'mobile_money';
            }

            // Préparer les données pour le paiement
            $paymentRequestData = [
                'commande_id' => $commande->id_commande,
                'payment_method' => $paymentMethod,
                'language' => 'fr',
            ];

            // Valider les données
            $validator = \Validator::make($paymentRequestData, [
                'commande_id' => 'required|integer|exists:commandes,id_commande',
                'language' => 'nullable|in:fr,en',
                'payment_method' => 'nullable|in:credit_card,mobile_money',
            ]);
            
            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Données de paiement invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Vérifier que la commande n'est pas déjà payée
            $existingPayment = Payment::where('id_commande', $commande->id_commande)
                ->where('statut_payment', 'paye')
                ->first();

            if ($existingPayment) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est déjà payée',
                ], 400);
            }

            // Calculer le montant total
            $montantTotal = $commande->montant_total ?? $commande->getTotal();

            // Déterminer les channels Easypay selon le choix
            $channels = [];
            $methodePayment = 'mobile_money';
            
            if ($paymentMethod === 'credit_card') {
                $channels = [['channel' => 'CREDIT CARD']];
                $methodePayment = 'carte_bancaire';
            } else {
                $channels = [['channel' => 'MOBILE MONEY']];
                $methodePayment = 'mobile_money';
            }

            // Préparer les données pour Easypay
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $backendUrl = config('app.url', 'http://localhost:8000');
            
            // Charger la relation utilisateur
            $commande->load('utilisateur');
            
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
                'language' => 'fr',
                'channels' => $channels,
            ];

            // Appeler EasypayService directement
            $easypayService = app(EasypayService::class);
            $result = $easypayService->initializeTransaction($easypayData);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                ], 500);
            }

            // Enregistrer le paiement en base de données
            $payment = Payment::create([
                'id_commande' => $commande->id_commande,
                'montant' => $montantTotal,
                'methode' => $methodePayment,
                'statut_payment' => 'en_attente',
                'transaction_ref' => $result['reference'],
            ]);

            // Envoyer l'email avec le réçu PDF lors de la redirection vers EasyPay
            try {
                \Log::info('Envoi de l\'email avec réçu PDF lors de l\'initialisation du paiement EasyPay', [
                    'payment_id' => $payment->id_payment,
                    'commande_id' => $commande->id_commande,
                    'email' => $commande->utilisateur->email
                ]);
                
                Mail::to($commande->utilisateur->email)->send(new PaymentReceiptMail($payment));
                
                \Log::info('Email avec réçu PDF envoyé avec succès lors de l\'initialisation du paiement', [
                    'payment_id' => $payment->id_payment,
                    'email' => $commande->utilisateur->email
                ]);
            } catch (\Exception $e) {
                // Log l'erreur mais ne fait pas échouer l'initialisation du paiement
                \Log::error('Erreur lors de l\'envoi du réçu de paiement lors de l\'initialisation', [
                    'payment_id' => $payment->id_payment,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            DB::commit();

            // Retourner la réponse
            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès',
                'data' => [
                    'payment_id' => $payment->id_payment ?? null,
                    'reference' => $result['reference'],
                    'redirect_url' => $result['redirect_url'],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors du traitement du paiement', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une commande de l'historique (soft delete ou hard delete selon les règles métier)
     * 
     * DELETE /api/orders/{id}
     * 
     * L'utilisateur ne peut supprimer que ses propres commandes
     * Les commandes avec certains statuts peuvent ne pas être supprimables
     */
    public function destroy(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la commande
            $commande = Commande::where('id_commande', $id)
                ->where('id_utilisateur', $utilisateur->id_utilisateur)
                ->first();
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable ou vous n\'avez pas le droit de la supprimer',
                ], 404);
            }
            
            // Vérifier si la commande peut être supprimée
            // Les commandes en cours ou livrées ne peuvent généralement pas être supprimées
            $statutsNonSupprimables = ['en_preparation', 'pret', 'livree'];
            if (in_array($commande->statut, $statutsNonSupprimables)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une commande avec le statut "' . $this->getStatutLisible($commande->statut) . '"',
                ], 400);
            }
            
            // Supprimer les articles associés
            $commande->articles()->delete();
            
            // Supprimer la commande
            $commande->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès',
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la commande',
                'error' => $e->getMessage(),
            ], 500);
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
            \Log::error('Erreur lors de la création de la notification', [
                'user_id' => $idUtilisateur,
                'commande_id' => $idCommande,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifier tous les employés d'une nouvelle commande
     * 
     * @param Commande $commande
     * @return void
     */
    private function notifyEmployeesOfNewOrder(Commande $commande)
    {
        try {
            // Récupérer tous les employés actifs (employe, gerant, admin)
            $employes = Utilisateur::whereIn('role', ['employe', 'gerant', 'admin'])
                ->where('statut_compte', 'actif')
                ->get();

            $nombreNotifies = 0;

            foreach ($employes as $employe) {
                try {
                    // Créer une notification pour chaque employé
                    $this->createNotification(
                        $employe->id_utilisateur,
                        $commande->id_commande,
                        'commande',
                        'Nouvelle commande',
                        "Nouvelle commande #{$commande->numero_commande} de {$commande->utilisateur->nom} {$commande->utilisateur->prenom}. Montant: " . number_format($commande->montant_total * 2000, 0, ',', ' ') . " CDF"
                    );
                    $nombreNotifies++;
                } catch (\Exception $e) {
                    // Log l'erreur pour cet employé mais continue avec les autres
                    \Log::error('Erreur lors de la notification d\'un employé', [
                        'employe_id' => $employe->id_utilisateur,
                        'commande_id' => $commande->id_commande,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            \Log::info('Notifications employés envoyées', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'employes_notifies' => $nombreNotifies,
                'total_employes' => $employes->count(),
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            \Log::error('Erreur lors de la notification des employés', [
                'commande_id' => $commande->id_commande ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gérer le parrainage lors de la première commande du filleul
     * 
     * @param Commande $commande
     * @param Utilisateur $utilisateur
     * @return void
     */
    private function handlePremiereCommandeParrainage(Commande $commande, Utilisateur $utilisateur)
    {
        try {
            // Vérifier si c'est la première commande de l'utilisateur
            $nombreCommandes = Commande::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('statut', '!=', 'panier')
                ->where('id_commande', '!=', $commande->id_commande)
                ->count();
            
            // Si ce n'est pas la première commande, ne rien faire
            if ($nombreCommandes > 0) {
                return;
            }
            
            // Vérifier si l'utilisateur a un parrain
            if (!$utilisateur->parrain_id) {
                return;
            }
            
            // Récupérer le parrainage
            $parrainage = Parrainage::where('id_filleul', $utilisateur->id_utilisateur)
                ->where('premiere_commande_faite', false)
                ->first();
            
            // Si le parrainage n'existe pas ou si les points ont déjà été attribués, ne rien faire
            if (!$parrainage) {
                return;
            }
            
            // Récupérer les points de première commande depuis parametres_fidelite
            $parametresFidelite = DB::table('parametres_fidelite')
                ->where('actif', true)
                ->orderBy('date_debut_application', 'desc')
                ->first();
            
            $pointsPremiereCommande = $parametresFidelite ? $parametresFidelite->points_premiere_commande : 20; // Default 20 si pas trouvé
            
            // Récupérer le parrain
            $parrain = Utilisateur::find($parrainage->id_parrain);
            
            if (!$parrain) {
                \Log::error('Parrain non trouvé pour le parrainage', [
                    'parrainage_id' => $parrainage->id_parrainage,
                    'parrain_id' => $parrainage->id_parrain,
                ]);
                return;
            }
            
            // Attribuer les points au parrain
            $parrain->increment('points_balance', $pointsPremiereCommande);
            
            // Mettre à jour le parrainage
            $parrainage->update([
                'premiere_commande_faite' => true,
                'date_premiere_commande' => now(),
                'points_premiere_commande' => $pointsPremiereCommande,
            ]);
            
            // Envoyer une notification au parrain
            Notification::create([
                'id_utilisateur' => $parrain->id_utilisateur,
                'id_commande' => $commande->id_commande,
                'type_notification' => 'system',
                'titre' => 'Première commande de votre filleul',
                'message' => "{$utilisateur->prenom} {$utilisateur->nom} a effectué sa première commande #{$commande->numero_commande}. Vous avez gagné {$pointsPremiereCommande} points supplémentaires !",
                'lu' => false,
                'date_creation' => now(),
            ]);
            
            \Log::info('Points de première commande attribués au parrain', [
                'parrain_id' => $parrain->id_utilisateur,
                'filleul_id' => $utilisateur->id_utilisateur,
                'commande_id' => $commande->id_commande,
                'points_attribues' => $pointsPremiereCommande,
            ]);
            
        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer la création de la commande
            \Log::error('Erreur lors de la gestion du parrainage pour la première commande', [
                'commande_id' => $commande->id_commande ?? null,
                'utilisateur_id' => $utilisateur->id_utilisateur ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
