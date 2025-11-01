<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\AssignOrder;
use App\Models\Commande;
use App\Models\Payment;
use App\Models\Utilisateur;
use App\Mail\CommandeConfirmationMail;
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
}
