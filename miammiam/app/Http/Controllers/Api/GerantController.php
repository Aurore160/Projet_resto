<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Requests\AssignOrder;
use App\Models\MenuItem;
use App\Models\Commande;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GerantController extends Controller
{
    // Liste tous les plats (gérant)
    public function listMenuItems()
    {
        try {
            $menuItems = MenuItem::with('categorie')
                                  ->orderBy('date_creation', 'desc')
                                  ->get();
            
            return response()->json([
                'success' => true,
                'data' => $menuItems,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Ajoute un nouveau plat (gérant uniquement)
    public function createMenuItem(CreateMenuItemRequest $request)
    {
        try {
            $data = $request->validated();
            
            $menuItem = MenuItem::create($data);
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat ajouté avec succès',
                'data' => $menuItem->load('categorie'),
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Modifie un plat (gérant uniquement)
    public function updateMenuItem(UpdateMenuItemRequest $request, $id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            $data = $request->validated();
            $menuItem->update($data);
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat modifié avec succès',
                'data' => $menuItem->load('categorie'),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Supprime un plat (gérant uniquement)
    public function deleteMenuItem($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            $platNom = $menuItem->nom;
            $menuItem->delete();
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat supprimé avec succès',
                'data' => [
                    'plat_supprime' => $platNom,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Consulter un plat spécifique (gérant)
    public function showMenuItem($id)
    {
        try {
            $menuItem = MenuItem::with('categorie')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $menuItem,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter les commandes en cours (pour le gérant)
     * 
     * GET /api/gerant/orders/in-progress
     * 
     * Accessible uniquement aux gérants
     * Retourne les commandes en cours avec filtres dynamiques
     * 
     * Paramètres de requête optionnels :
     * - statut : Filtrer par statut (en_attente, confirmee, en_preparation, pret)
     * - type_commande : Filtrer par type (sur_place, livraison)
     * - date_debut : Date de début (format: YYYY-MM-DD)
     * - date_fin : Date de fin (format: YYYY-MM-DD)
     * - tri : Champ de tri (date_commande, date_modification, montant_total) - défaut: date_modification
     * - ordre : Direction de tri (asc, desc) - défaut: desc
     */
    public function listOrdersInProgress(Request $request)
    {
        try {
            // Définir les statuts considérés comme "en cours"
            $statutsEnCours = ['en_attente', 'confirmee', 'en_preparation', 'pret'];
            
            // Construire la requête de base : commandes en cours (exclure paniers, livrées, annulées)
            $query = Commande::whereIn('statut', $statutsEnCours)
                ->with(['utilisateur', 'livreur', 'articles.menuItem']); // Charger toutes les relations nécessaires
            
            // Filtre par statut spécifique (si fourni et valide)
            $statutFilter = $request->query('statut');
            if ($statutFilter && in_array($statutFilter, $statutsEnCours)) {
                $query->where('statut', $statutFilter);
            }
            
            // Filtre par type de commande
            $typeFilter = $request->query('type_commande');
            if ($typeFilter && in_array($typeFilter, ['sur_place', 'livraison'])) {
                $query->where('type_commande', $typeFilter);
            }
            
            // Filtre par date de début
            $dateDebut = $request->query('date_debut');
            if ($dateDebut) {
                $query->whereDate('date_commande', '>=', $dateDebut);
            }
            
            // Filtre par date de fin
            $dateFin = $request->query('date_fin');
            if ($dateFin) {
                $query->whereDate('date_commande', '<=', $dateFin);
            }
            
            // Tri personnalisable
            $tri = $request->query('tri', 'date_modification'); // Par défaut: date_modification
            $ordre = $request->query('ordre', 'desc'); // Par défaut: desc
            
            // Valider et appliquer le tri
            $champsTriValides = ['date_commande', 'date_modification', 'montant_total'];
            if (in_array($tri, $champsTriValides)) {
                $ordre = strtolower($ordre) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($tri, $ordre);
            } else {
                // Tri par défaut si le champ fourni n'est pas valide
                $query->orderBy('date_modification', 'desc');
            }
            
            // Trier aussi par id_commande pour un ordre stable (si même date)
            $query->orderBy('id_commande', 'desc');
            
            // Exécuter la requête
            $commandes = $query->get();
            
            // Formater les données pour l'affichage dans un tableau dynamique
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le temps écoulé depuis la dernière modification
                $tempsEcoule = $commande->date_modification->diffForHumans();
                
                // Calculer le pourcentage de progression
                $progression = $this->calculerProgression($commande->statut);
                
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'prix_unitaire' => $article->prix_unitaire,
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'sous_total' => $article->getSousTotal(),
                    ];
                });
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => (float) $commande->montant_total,
                    'frais_livraison' => (float) $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => (float) $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue 
                        ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') 
                        : null,
                    'temps_ecoule' => $tempsEcoule,
                    'progression' => $progression,
                    'nb_articles' => $commande->getTotalArticles(),
                    'adresse_livraison' => $commande->adresse_livraison,
                    'commentaire' => $commande->commentaire,
                    'instructions_speciales' => $commande->instructions_speciales,
                    // Informations sur le client
                    'client' => $commande->utilisateur ? [
                        'id' => $commande->utilisateur->id_utilisateur,
                        'nom' => $commande->utilisateur->nom,
                        'prenom' => $commande->utilisateur->prenom,
                        'email' => $commande->utilisateur->email,
                        'telephone' => $commande->utilisateur->telephone,
                    ] : null,
                    // Informations sur le livreur (si assigné)
                    'livreur' => $commande->livreur ? [
                        'id' => $commande->livreur->id_utilisateur,
                        'nom' => $commande->livreur->nom,
                        'prenom' => $commande->livreur->prenom,
                        'telephone' => $commande->livreur->telephone,
                    ] : null,
                    // Liste des articles (résumé)
                    'articles' => $articlesFormates,
                ];
            });
            
            // Calculer des statistiques pour le tableau
            $stats = [
                'total_commandes' => $commandesFormatees->count(),
                'par_statut' => $commandesFormatees->groupBy('statut')->map->count(),
                'par_type' => $commandesFormatees->groupBy('type_commande')->map->count(),
                'montant_total' => $commandesFormatees->sum('montant_total'),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'statistiques' => $stats,
                    'filtres_appliques' => [
                        'statut' => $statutFilter ?? null,
                        'type_commande' => $typeFilter ?? null,
                        'date_debut' => $dateDebut ?? null,
                        'date_fin' => $dateFin ?? null,
                        'tri' => $tri,
                        'ordre' => $ordre,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des commandes en cours (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en cours',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter les détails d'une commande en cours (pour le gérant)
     * 
     * GET /api/gerant/orders/{id}
     * 
     * Accessible uniquement aux gérants
     * Retourne tous les détails d'une commande spécifique
     */
    public function showOrderDetails($id)
    {
        try {
            // Récupérer la commande avec toutes ses relations
            $commande = Commande::with([
                'utilisateur', 
                'livreur', 
                'articles.menuItem.categorie'
            ])->findOrFail($id);
            
            // Vérifier que la commande est en cours (pas terminée)
            $statutsEnCours = ['en_attente', 'confirmee', 'en_preparation', 'pret'];
            if (!in_array($commande->statut, $statutsEnCours) && $commande->statut !== 'panier') {
                // Autoriser quand même l'accès aux commandes terminées pour consultation historique
                // Mais on peut ajouter un message informatif
            }
            
            // Formater les articles avec tous les détails
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'prix_unitaire' => $article->prix_unitaire,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                        'categorie' => $article->menuItem->categorie ? [
                            'id' => $article->menuItem->categorie->id_categorie,
                            'nom' => $article->menuItem->categorie->nom,
                        ] : null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => (float) $article->prix_unitaire,
                    'sous_total' => (float) $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Calculer le temps écoulé
            $tempsEcoule = $commande->date_modification->diffForHumans();
            $progression = $this->calculerProgression($commande->statut);
            
            // Formater la réponse complète
            $commandeFormatee = [
                'id_commande' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'statut' => $commande->statut,
                'statut_lisible' => $this->getStatutLisible($commande->statut),
                'type_commande' => $commande->type_commande,
                'montant_total' => (float) $commande->montant_total,
                'frais_livraison' => (float) $commande->frais_livraison,
                'points_utilises' => $commande->points_utilises,
                'reduction_points' => (float) $commande->reduction_points,
                'montant_final' => (float) ($commande->montant_total + $commande->frais_livraison - $commande->reduction_points),
                'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                'heure_arrivee_prevue' => $commande->heure_arrivee_prevue 
                    ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') 
                    : null,
                'temps_ecoule' => $tempsEcoule,
                'progression' => $progression,
                'nb_articles' => $commande->getTotalArticles(),
                'adresse_livraison' => $commande->adresse_livraison,
                'commentaire' => $commande->commentaire,
                'instructions_speciales' => $commande->instructions_speciales,
                // Informations complètes sur le client
                'client' => $commande->utilisateur ? [
                    'id' => $commande->utilisateur->id_utilisateur,
                    'nom' => $commande->utilisateur->nom,
                    'prenom' => $commande->utilisateur->prenom,
                    'email' => $commande->utilisateur->email,
                    'telephone' => $commande->utilisateur->telephone,
                    'adresse' => $commande->utilisateur->adresse ?? null,
                ] : null,
                // Informations complètes sur le livreur (si assigné)
                'livreur' => $commande->livreur ? [
                    'id' => $commande->livreur->id_utilisateur,
                    'nom' => $commande->livreur->nom,
                    'prenom' => $commande->livreur->prenom,
                    'email' => $commande->livreur->email,
                    'telephone' => $commande->livreur->telephone,
                ] : null,
                // Liste complète des articles
                'articles' => $articlesFormates,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $commandeFormatee,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails de la commande (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
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
            'en_attente' => 10,
            'confirmee' => 30,
            'en_preparation' => 60,
            'pret' => 90,
            'livree' => 100,
            'annulee' => 0,
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
     * Lister les livreurs disponibles (pour le gérant)
     * 
     * GET /api/gerant/delivery-employees
     * 
     * Accessible uniquement aux gérants
     * Retourne la liste de tous les livreurs actifs disponibles pour l'assignation de commandes
     */
    public function listDeliveryEmployees()
    {
        try {
            // Récupérer tous les livreurs actifs
            $livreurs = DB::table('employe')
                ->join('utilisateur', 'employe.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->where('employe.role_specifique', 'livreur')
                ->where('employe.statut', 'actif')
                ->select(
                    'employe.id_employe',
                    'employe.id_utilisateur',
                    'employe.matricule',
                    'employe.role_specifique',
                    'employe.date_embauche',
                    'employe.statut',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone'
                )
                ->orderBy('utilisateur.nom')
                ->orderBy('utilisateur.prenom')
                ->get();
            
            // Compter le nombre de commandes en cours par livreur
            $livreursAvecStats = $livreurs->map(function ($livreur) {
                $nbCommandesEnCours = Commande::where('id_livreur', $livreur->id_utilisateur)
                    ->whereIn('statut', ['en_attente', 'confirmee', 'en_preparation', 'pret'])
                    ->count();
                
                return [
                    'id_employe' => $livreur->id_employe,
                    'id_utilisateur' => $livreur->id_utilisateur,
                    'matricule' => $livreur->matricule,
                    'nom' => $livreur->nom,
                    'prenom' => $livreur->prenom,
                    'nom_complet' => $livreur->nom . ' ' . $livreur->prenom,
                    'email' => $livreur->email,
                    'telephone' => $livreur->telephone,
                    'date_embauche' => $livreur->date_embauche,
                    'statut' => $livreur->statut,
                    'nb_commandes_en_cours' => $nbCommandesEnCours,
                    'disponible' => $nbCommandesEnCours < 5, // Considéré disponible si moins de 5 commandes
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $livreursAvecStats,
                'meta' => [
                    'total_livreurs' => $livreursAvecStats->count(),
                    'livreurs_disponibles' => $livreursAvecStats->where('disponible', true)->count(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des livreurs disponibles (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des livreurs disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assigner une commande à un livreur (pour le gérant)
     * 
     * PUT /api/gerant/orders/{id}/assign
     * 
     * Accessible uniquement aux gérants
     * Permet d'attribuer une commande de type 'livraison' à un livreur
     */
    public function assignOrderToDeliveryEmployee(AssignOrder $request, $id)
    {
        try {
            // Récupérer la commande
            $commande = Commande::with(['utilisateur', 'livreur'])->find($id);
            
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
            
            // Sauvegarder l'ancien livreur pour les logs
            $ancienLivreurId = $commande->id_livreur;
            $ancienLivreur = $ancienLivreurId ? Utilisateur::find($ancienLivreurId) : null;
            
            // Attribuer la commande au livreur
            $commande->update(['id_livreur' => $idLivreur]);
            
            // Recharger la commande pour avoir les relations à jour
            $commande->refresh();
            $commande->load('livreur', 'utilisateur');
            
            // Logger l'attribution
            Log::info('Commande attribuée à un livreur par le gérant', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_livreur_id' => $ancienLivreurId,
                'nouveau_livreur_id' => $idLivreur,
                'livreur_nom' => $livreur->nom . ' ' . $livreur->prenom,
                'gerant_id' => $request->user()->id_utilisateur,
                'gerant_nom' => $request->user()->nom . ' ' . $request->user()->prenom,
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
                        'statut_lisible' => $this->getStatutLisible($commande->statut),
                        'livreur' => $commande->livreur ? [
                            'id_utilisateur' => $commande->livreur->id_utilisateur,
                            'nom' => $commande->livreur->nom,
                            'prenom' => $commande->livreur->prenom,
                            'email' => $commande->livreur->email,
                            'telephone' => $commande->livreur->telephone,
                        ] : null,
                        'client' => $commande->utilisateur ? [
                            'id_utilisateur' => $commande->utilisateur->id_utilisateur,
                            'nom' => $commande->utilisateur->nom,
                            'prenom' => $commande->utilisateur->prenom,
                        ] : null,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                    'livreur_ancien' => $ancienLivreur ? [
                        'id_utilisateur' => $ancienLivreur->id_utilisateur,
                        'nom' => $ancienLivreur->nom,
                        'prenom' => $ancienLivreur->prenom,
                    ] : null,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'attribution de la commande à un livreur (gérant)', [
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

    // Méthode privée pour invalider tous les caches du menu
    private function clearMenuCache()
    {
        // Supprimer les caches principaux
        Cache::forget('menu_complet');
        Cache::forget('plats_du_jour');
        
        // Laravel ne permet pas de supprimer par pattern facilement
        // mais on peut supprimer les caches les plus courants
        // Le cache se régénérera automatiquement à la prochaine requête
    }
}

     * 
     * Paramètres de requête optionnels :
     * - statut : Filtrer par statut (en_attente, confirmee, en_preparation, pret)
     * - type_commande : Filtrer par type (sur_place, livraison)
     * - date_debut : Date de début (format: YYYY-MM-DD)
     * - date_fin : Date de fin (format: YYYY-MM-DD)
     * - tri : Champ de tri (date_commande, date_modification, montant_total) - défaut: date_modification
     * - ordre : Direction de tri (asc, desc) - défaut: desc
     */
    public function listOrdersInProgress(Request $request)
    {
        try {
            // Définir les statuts considérés comme "en cours"
            $statutsEnCours = ['en_attente', 'confirmee', 'en_preparation', 'pret'];
            
            // Construire la requête de base : commandes en cours (exclure paniers, livrées, annulées)
            $query = Commande::whereIn('statut', $statutsEnCours)
                ->with(['utilisateur', 'livreur', 'articles.menuItem']); // Charger toutes les relations nécessaires
            
            // Filtre par statut spécifique (si fourni et valide)
            $statutFilter = $request->query('statut');
            if ($statutFilter && in_array($statutFilter, $statutsEnCours)) {
                $query->where('statut', $statutFilter);
            }
            
            // Filtre par type de commande
            $typeFilter = $request->query('type_commande');
            if ($typeFilter && in_array($typeFilter, ['sur_place', 'livraison'])) {
                $query->where('type_commande', $typeFilter);
            }
            
            // Filtre par date de début
            $dateDebut = $request->query('date_debut');
            if ($dateDebut) {
                $query->whereDate('date_commande', '>=', $dateDebut);
            }
            
            // Filtre par date de fin
            $dateFin = $request->query('date_fin');
            if ($dateFin) {
                $query->whereDate('date_commande', '<=', $dateFin);
            }
            
            // Tri personnalisable
            $tri = $request->query('tri', 'date_modification'); // Par défaut: date_modification
            $ordre = $request->query('ordre', 'desc'); // Par défaut: desc
            
            // Valider et appliquer le tri
            $champsTriValides = ['date_commande', 'date_modification', 'montant_total'];
            if (in_array($tri, $champsTriValides)) {
                $ordre = strtolower($ordre) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($tri, $ordre);
            } else {
                // Tri par défaut si le champ fourni n'est pas valide
                $query->orderBy('date_modification', 'desc');
            }
            
            // Trier aussi par id_commande pour un ordre stable (si même date)
            $query->orderBy('id_commande', 'desc');
            
            // Exécuter la requête
            $commandes = $query->get();
            
            // Formater les données pour l'affichage dans un tableau dynamique
            $commandesFormatees = $commandes->map(function ($commande) {
                // Calculer le temps écoulé depuis la dernière modification
                $tempsEcoule = $commande->date_modification->diffForHumans();
                
                // Calculer le pourcentage de progression
                $progression = $this->calculerProgression($commande->statut);
                
                // Formater les articles de la commande
                $articlesFormates = $commande->articles->map(function ($article) {
                    return [
                        'id' => $article->id_commande_article,
                        'plat' => [
                            'id' => $article->menuItem->id_menuitem ?? null,
                            'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                            'prix_unitaire' => $article->prix_unitaire,
                            'photo_url' => $article->menuItem->photo_url ?? null,
                        ],
                        'quantite' => $article->quantite,
                        'sous_total' => $article->getSousTotal(),
                    ];
                });
                
                return [
                    'id_commande' => $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'statut_lisible' => $this->getStatutLisible($commande->statut),
                    'type_commande' => $commande->type_commande,
                    'montant_total' => (float) $commande->montant_total,
                    'frais_livraison' => (float) $commande->frais_livraison,
                    'points_utilises' => $commande->points_utilises,
                    'reduction_points' => (float) $commande->reduction_points,
                    'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                    'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                    'heure_arrivee_prevue' => $commande->heure_arrivee_prevue 
                        ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') 
                        : null,
                    'temps_ecoule' => $tempsEcoule,
                    'progression' => $progression,
                    'nb_articles' => $commande->getTotalArticles(),
                    'adresse_livraison' => $commande->adresse_livraison,
                    'commentaire' => $commande->commentaire,
                    'instructions_speciales' => $commande->instructions_speciales,
                    // Informations sur le client
                    'client' => $commande->utilisateur ? [
                        'id' => $commande->utilisateur->id_utilisateur,
                        'nom' => $commande->utilisateur->nom,
                        'prenom' => $commande->utilisateur->prenom,
                        'email' => $commande->utilisateur->email,
                        'telephone' => $commande->utilisateur->telephone,
                    ] : null,
                    // Informations sur le livreur (si assigné)
                    'livreur' => $commande->livreur ? [
                        'id' => $commande->livreur->id_utilisateur,
                        'nom' => $commande->livreur->nom,
                        'prenom' => $commande->livreur->prenom,
                        'telephone' => $commande->livreur->telephone,
                    ] : null,
                    // Liste des articles (résumé)
                    'articles' => $articlesFormates,
                ];
            });
            
            // Calculer des statistiques pour le tableau
            $stats = [
                'total_commandes' => $commandesFormatees->count(),
                'par_statut' => $commandesFormatees->groupBy('statut')->map->count(),
                'par_type' => $commandesFormatees->groupBy('type_commande')->map->count(),
                'montant_total' => $commandesFormatees->sum('montant_total'),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $commandesFormatees,
                'meta' => [
                    'statistiques' => $stats,
                    'filtres_appliques' => [
                        'statut' => $statutFilter ?? null,
                        'type_commande' => $typeFilter ?? null,
                        'date_debut' => $dateDebut ?? null,
                        'date_fin' => $dateFin ?? null,
                        'tri' => $tri,
                        'ordre' => $ordre,
                    ],
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des commandes en cours (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes en cours',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter les détails d'une commande en cours (pour le gérant)
     * 
     * GET /api/gerant/orders/{id}
     * 
     * Accessible uniquement aux gérants
     * Retourne tous les détails d'une commande spécifique
     */
    public function showOrderDetails($id)
    {
        try {
            // Récupérer la commande avec toutes ses relations
            $commande = Commande::with([
                'utilisateur', 
                'livreur', 
                'articles.menuItem.categorie'
            ])->findOrFail($id);
            
            // Vérifier que la commande est en cours (pas terminée)
            $statutsEnCours = ['en_attente', 'confirmee', 'en_preparation', 'pret'];
            if (!in_array($commande->statut, $statutsEnCours) && $commande->statut !== 'panier') {
                // Autoriser quand même l'accès aux commandes terminées pour consultation historique
                // Mais on peut ajouter un message informatif
            }
            
            // Formater les articles avec tous les détails
            $articlesFormates = $commande->articles->map(function ($article) {
                return [
                    'id' => $article->id_commande_article,
                    'plat' => [
                        'id' => $article->menuItem->id_menuitem ?? null,
                        'nom' => $article->menuItem->nom ?? 'Plat supprimé',
                        'description' => $article->menuItem->description ?? null,
                        'prix_unitaire' => $article->prix_unitaire,
                        'photo_url' => $article->menuItem->photo_url ?? null,
                        'categorie' => $article->menuItem->categorie ? [
                            'id' => $article->menuItem->categorie->id_categorie,
                            'nom' => $article->menuItem->categorie->nom,
                        ] : null,
                    ],
                    'quantite' => $article->quantite,
                    'prix_unitaire' => (float) $article->prix_unitaire,
                    'sous_total' => (float) $article->getSousTotal(),
                    'instructions' => $article->instructions ?? null,
                ];
            });
            
            // Calculer le temps écoulé
            $tempsEcoule = $commande->date_modification->diffForHumans();
            $progression = $this->calculerProgression($commande->statut);
            
            // Formater la réponse complète
            $commandeFormatee = [
                'id_commande' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'statut' => $commande->statut,
                'statut_lisible' => $this->getStatutLisible($commande->statut),
                'type_commande' => $commande->type_commande,
                'montant_total' => (float) $commande->montant_total,
                'frais_livraison' => (float) $commande->frais_livraison,
                'points_utilises' => $commande->points_utilises,
                'reduction_points' => (float) $commande->reduction_points,
                'montant_final' => (float) ($commande->montant_total + $commande->frais_livraison - $commande->reduction_points),
                'date_commande' => $commande->date_commande->format('Y-m-d H:i:s'),
                'date_modification' => $commande->date_modification->format('Y-m-d H:i:s'),
                'heure_arrivee_prevue' => $commande->heure_arrivee_prevue 
                    ? $commande->heure_arrivee_prevue->format('Y-m-d H:i:s') 
                    : null,
                'temps_ecoule' => $tempsEcoule,
                'progression' => $progression,
                'nb_articles' => $commande->getTotalArticles(),
                'adresse_livraison' => $commande->adresse_livraison,
                'commentaire' => $commande->commentaire,
                'instructions_speciales' => $commande->instructions_speciales,
                // Informations complètes sur le client
                'client' => $commande->utilisateur ? [
                    'id' => $commande->utilisateur->id_utilisateur,
                    'nom' => $commande->utilisateur->nom,
                    'prenom' => $commande->utilisateur->prenom,
                    'email' => $commande->utilisateur->email,
                    'telephone' => $commande->utilisateur->telephone,
                    'adresse' => $commande->utilisateur->adresse ?? null,
                ] : null,
                // Informations complètes sur le livreur (si assigné)
                'livreur' => $commande->livreur ? [
                    'id' => $commande->livreur->id_utilisateur,
                    'nom' => $commande->livreur->nom,
                    'prenom' => $commande->livreur->prenom,
                    'email' => $commande->livreur->email,
                    'telephone' => $commande->livreur->telephone,
                ] : null,
                // Liste complète des articles
                'articles' => $articlesFormates,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $commandeFormatee,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails de la commande (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'commande_id' => $id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
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
            'en_attente' => 10,
            'confirmee' => 30,
            'en_preparation' => 60,
            'pret' => 90,
            'livree' => 100,
            'annulee' => 0,
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
     * Lister les livreurs disponibles (pour le gérant)
     * 
     * GET /api/gerant/delivery-employees
     * 
     * Accessible uniquement aux gérants
     * Retourne la liste de tous les livreurs actifs disponibles pour l'assignation de commandes
     */
    public function listDeliveryEmployees()
    {
        try {
            // Récupérer tous les livreurs actifs
            $livreurs = DB::table('employe')
                ->join('utilisateur', 'employe.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->where('employe.role_specifique', 'livreur')
                ->where('employe.statut', 'actif')
                ->select(
                    'employe.id_employe',
                    'employe.id_utilisateur',
                    'employe.matricule',
                    'employe.role_specifique',
                    'employe.date_embauche',
                    'employe.statut',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone'
                )
                ->orderBy('utilisateur.nom')
                ->orderBy('utilisateur.prenom')
                ->get();
            
            // Compter le nombre de commandes en cours par livreur
            $livreursAvecStats = $livreurs->map(function ($livreur) {
                $nbCommandesEnCours = Commande::where('id_livreur', $livreur->id_utilisateur)
                    ->whereIn('statut', ['en_attente', 'confirmee', 'en_preparation', 'pret'])
                    ->count();
                
                return [
                    'id_employe' => $livreur->id_employe,
                    'id_utilisateur' => $livreur->id_utilisateur,
                    'matricule' => $livreur->matricule,
                    'nom' => $livreur->nom,
                    'prenom' => $livreur->prenom,
                    'nom_complet' => $livreur->nom . ' ' . $livreur->prenom,
                    'email' => $livreur->email,
                    'telephone' => $livreur->telephone,
                    'date_embauche' => $livreur->date_embauche,
                    'statut' => $livreur->statut,
                    'nb_commandes_en_cours' => $nbCommandesEnCours,
                    'disponible' => $nbCommandesEnCours < 5, // Considéré disponible si moins de 5 commandes
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $livreursAvecStats,
                'meta' => [
                    'total_livreurs' => $livreursAvecStats->count(),
                    'livreurs_disponibles' => $livreursAvecStats->where('disponible', true)->count(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des livreurs disponibles (gérant)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des livreurs disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assigner une commande à un livreur (pour le gérant)
     * 
     * PUT /api/gerant/orders/{id}/assign
     * 
     * Accessible uniquement aux gérants
     * Permet d'attribuer une commande de type 'livraison' à un livreur
     */
    public function assignOrderToDeliveryEmployee(AssignOrder $request, $id)
    {
        try {
            // Récupérer la commande
            $commande = Commande::with(['utilisateur', 'livreur'])->find($id);
            
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
            
            // Sauvegarder l'ancien livreur pour les logs
            $ancienLivreurId = $commande->id_livreur;
            $ancienLivreur = $ancienLivreurId ? Utilisateur::find($ancienLivreurId) : null;
            
            // Attribuer la commande au livreur
            $commande->update(['id_livreur' => $idLivreur]);
            
            // Recharger la commande pour avoir les relations à jour
            $commande->refresh();
            $commande->load('livreur', 'utilisateur');
            
            // Logger l'attribution
            Log::info('Commande attribuée à un livreur par le gérant', [
                'commande_id' => $commande->id_commande,
                'numero_commande' => $commande->numero_commande,
                'ancien_livreur_id' => $ancienLivreurId,
                'nouveau_livreur_id' => $idLivreur,
                'livreur_nom' => $livreur->nom . ' ' . $livreur->prenom,
                'gerant_id' => $request->user()->id_utilisateur,
                'gerant_nom' => $request->user()->nom . ' ' . $request->user()->prenom,
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
                        'statut_lisible' => $this->getStatutLisible($commande->statut),
                        'livreur' => $commande->livreur ? [
                            'id_utilisateur' => $commande->livreur->id_utilisateur,
                            'nom' => $commande->livreur->nom,
                            'prenom' => $commande->livreur->prenom,
                            'email' => $commande->livreur->email,
                            'telephone' => $commande->livreur->telephone,
                        ] : null,
                        'client' => $commande->utilisateur ? [
                            'id_utilisateur' => $commande->utilisateur->id_utilisateur,
                            'nom' => $commande->utilisateur->nom,
                            'prenom' => $commande->utilisateur->prenom,
                        ] : null,
                        'date_modification' => $commande->date_modification ? $commande->date_modification->format('Y-m-d H:i:s') : null,
                    ],
                    'livreur_ancien' => $ancienLivreur ? [
                        'id_utilisateur' => $ancienLivreur->id_utilisateur,
                        'nom' => $ancienLivreur->nom,
                        'prenom' => $ancienLivreur->prenom,
                    ] : null,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'attribution de la commande à un livreur (gérant)', [
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

    // Méthode privée pour invalider tous les caches du menu
    private function clearMenuCache()
    {
        // Supprimer les caches principaux
        Cache::forget('menu_complet');
        Cache::forget('plats_du_jour');
        
        // Laravel ne permet pas de supprimer par pattern facilement
        // mais on peut supprimer les caches les plus courants
        // Le cache se régénérera automatiquement à la prochaine requête
    }
}
