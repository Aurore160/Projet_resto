<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Models\Promotion;
use App\Models\PromoMenuItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

class PromotionController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Lister toutes les promotions actives et disponibles
     * 
     * GET /api/promotions
     * 
     * Query parameters:
     * - type_promotion: pourcentage, montant_fixe, offre_speciale, menu_special (optionnel)
     */
    public function index(Request $request)
    {
        try {
            // Récupérer les promotions disponibles (actives, non expirées, non épuisées)
            $query = Promotion::disponible();

            // Filtrer par type de promotion si fourni
            if ($request->has('type_promotion')) {
                $type = $request->input('type_promotion');
                if (in_array($type, ['pourcentage', 'montant_fixe', 'offre_speciale', 'menu_special'])) {
                    $query->where('type_promotion', $type);
                }
            }

            $promotions = $query->orderBy('date_debut', 'desc')->get();

            // Formater les données pour la réponse
            $promotionsData = $promotions->map(function ($promotion) {
                return [
                    'id_promo' => $promotion->id_promo,
                    'titre' => $promotion->titre,
                    'type_promotion' => $promotion->type_promotion,
                    'valeur' => (float) $promotion->valeur,
                    'valeur_minimum_panier' => (float) $promotion->valeur_minimum_panier,
                    'date_debut' => $promotion->date_debut ? $promotion->date_debut->format('Y-m-d H:i:s') : null,
                    'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                    'details' => $promotion->details,
                    'code_promo' => $promotion->code_promo,
                    'utilisations_max' => $promotion->utilisations_max,
                    'utilisations_actuelles' => $promotion->utilisations_actuelles,
                    'image_url' => $promotion->image_url,
                    'est_valide' => $promotion->estValide(),
                ];
            });

            return $this->successResponse([
                'promotions' => $promotionsData,
                'total' => $promotionsData->count(),
            ], 'Promotions récupérées avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des promotions',
                []
            );
        }
    }

    /**
     * Afficher les détails d'une promotion spécifique
     * 
     * GET /api/promotions/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $promotion = Promotion::with(['plats', 'createur'])
                ->find($id);

            if (!$promotion) {
                return $this->errorResponse('Promotion non trouvée', 404);
            }

            // Formater les données pour la réponse
            $promotionData = [
                'id_promo' => $promotion->id_promo,
                'titre' => $promotion->titre,
                'type_promotion' => $promotion->type_promotion,
                'valeur' => (float) $promotion->valeur,
                'valeur_minimum_panier' => (float) $promotion->valeur_minimum_panier,
                'date_debut' => $promotion->date_debut ? $promotion->date_debut->format('Y-m-d H:i:s') : null,
                'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                'applicabilite' => $promotion->applicabilite,
                'details' => $promotion->details,
                'code_promo' => $promotion->code_promo,
                'utilisations_max' => $promotion->utilisations_max,
                'utilisations_actuelles' => $promotion->utilisations_actuelles,
                'statut' => $promotion->statut,
                'image_url' => $promotion->image_url,
                'est_valide' => $promotion->estValide(),
                'plats' => $promotion->plats->map(function ($plat) use ($promotion) {
                    $prixPromo = PromoMenuItem::where('id_promo', $promotion->id_promo)
                        ->where('id_menuitem', $plat->id_menuitem)
                        ->first();
                    
                    return [
                        'id_menuitem' => $plat->id_menuitem,
                        'nom' => $plat->nom,
                        'description' => $plat->description,
                        'prix_original' => (float) $plat->prix,
                        'prix_promotionnel' => $prixPromo ? (float) $prixPromo->prix_promotionnel : null,
                        'photo_url' => $plat->photo_url,
                    ];
                }),
            ];

            return $this->successResponse($promotionData, 'Promotion récupérée avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération de la promotion',
                ['id_promo' => $id]
            );
        }
    }

    /**
     * Lister tous les plats en promotion
     * 
     * GET /api/promotions/plats
     * 
     * Retourne la liste des plats qui ont actuellement une promotion active
     * Inclut :
     * 1. Les plats avec promotions publiées explicitement (promo_menu_item)
     * 2. Les promotions actives appliquées automatiquement à tous les plats disponibles
     */
    public function plats(Request $request)
    {
        try {
            // Version ultra-simplifiée pour déboguer
            $platsData = collect();
            
            // Récupérer les promotions publiées
            $platsEnPromo = PromoMenuItem::with(['menuItem.categorie', 'promotion'])->get();
            
            \Log::info('Promotions publiées trouvées (avant filtrage)', [
                'count' => $platsEnPromo->count(),
                'details' => $platsEnPromo->map(function ($p) {
                    return [
                        'id_promomenuitem' => $p->id_promomenuitem,
                        'id_promo' => $p->id_promo,
                        'id_menuitem' => $p->id_menuitem,
                        'statut' => $p->statut,
                        'has_menuItem' => $p->menuItem ? true : false,
                        'has_promotion' => $p->promotion ? true : false,
                        'menuItem_statut_disponibilite' => $p->menuItem ? $p->menuItem->statut_disponibilite : null,
                        'promotion_statut' => $p->promotion ? $p->promotion->statut : null,
                        'promotion_date_debut' => $p->promotion && $p->promotion->date_debut ? $p->promotion->date_debut->format('Y-m-d H:i:s') : null,
                        'promotion_date_fin' => $p->promotion && $p->promotion->date_fin ? $p->promotion->date_fin->format('Y-m-d H:i:s') : null,
                    ];
                })->toArray(),
            ]);
            
            if ($platsEnPromo->isEmpty()) {
                return $this->successResponse([
                    'plats' => [],
                    'total' => 0,
                ], 'Aucun plat en promotion');
            }

            $platsPublies = $platsEnPromo->filter(function ($promoPlat) {
                // Vérifier que les relations existent
                if (!$promoPlat->menuItem || !$promoPlat->promotion) {
                    \Log::warning('Promotion publiée sans relation', [
                        'id_promo' => $promoPlat->id_promo,
                        'id_menuitem' => $promoPlat->id_menuitem,
                        'has_menuItem' => $promoPlat->menuItem ? true : false,
                        'has_promotion' => $promoPlat->promotion ? true : false,
                    ]);
                    return false;
                }
                
                // Vérifier que le plat est disponible
                if (!$promoPlat->menuItem->statut_disponibilite) {
                    \Log::info('Plat non disponible filtré', [
                        'id_menuitem' => $promoPlat->menuItem->id_menuitem,
                        'nom' => $promoPlat->menuItem->nom,
                        'statut_disponibilite' => $promoPlat->menuItem->statut_disponibilite,
                    ]);
                    return false;
                }
                
                // Vérifier le statut dans promo_menu_item (si null, considérer comme actif)
                if ($promoPlat->statut !== null && $promoPlat->statut !== 'active') {
                    \Log::info('Promotion publiée non active dans promo_menu_item', [
                        'id_promomenuitem' => $promoPlat->id_promomenuitem,
                        'statut' => $promoPlat->statut,
                    ]);
                    return false;
                }
                
                // Vérifier que la promotion principale existe et est active
                $promotion = $promoPlat->promotion;
                if ($promotion->statut !== 'active') {
                    \Log::info('Promotion principale non active filtrée', [
                        'id_promo' => $promotion->id_promo,
                        'titre' => $promotion->titre,
                        'statut' => $promotion->statut,
                    ]);
                    return false;
                }
                
                // TEMPORAIRE : Ignorer la vérification des dates pour déboguer
                // On affiche toutes les promotions actives, même si les dates sont dans le futur
                $now = now();
                
                \Log::info('Promotion acceptée (dates ignorées temporairement)', [
                    'id_promo' => $promotion->id_promo,
                    'id_menuitem' => $promoPlat->menuItem->id_menuitem,
                    'nom_plat' => $promoPlat->menuItem->nom,
                    'date_debut' => $promotion->date_debut ? $promotion->date_debut->format('Y-m-d H:i:s') : null,
                    'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                    'now' => $now->format('Y-m-d H:i:s'),
                ]);
                
                return true;
            })->map(function ($promoPlat) {
                $plat = $promoPlat->menuItem;
                $promotion = $promoPlat->promotion;
                
                $prixOriginal = (float) $plat->prix;
                $prixPromo = (float) $promoPlat->prix_promotionnel;
                $reduction = $prixOriginal - $prixPromo;
                $pourcentageReduction = $prixOriginal > 0 ? round(($reduction / $prixOriginal) * 100, 2) : 0;
                
                return [
                    'id_menuitem' => $plat->id_menuitem,
                    'nom' => $plat->nom,
                    'description' => $plat->description ?? '',
                    'prix_original' => $prixOriginal,
                    'prix_promotionnel' => $prixPromo,
                    'reduction' => $reduction,
                    'pourcentage_reduction' => $pourcentageReduction,
                    'photo_url' => $plat->photo_url,
                    'categorie' => $plat->categorie ? [
                        'id_categorie' => $plat->categorie->id_categorie,
                        'nom' => $plat->categorie->nom,
                    ] : null,
                    'promotion' => [
                        'id_promo' => $promotion->id_promo,
                        'titre' => $promotion->titre,
                        'type_promotion' => $promotion->type_promotion,
                        'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                    ],
                ];
            });

            $platsData = $platsData->merge($platsPublies);
            
            \Log::info('Plats publiés après filtrage', [
                'count' => $platsPublies->count(),
            ]);
            
            // 2. Récupérer toutes les promotions actives et valides (pour application automatique)
            $promotionsActives = Promotion::where('statut', 'active')
                ->where('date_debut', '<=', now())
                ->where('date_fin', '>=', now())
                ->where(function ($q) {
                    $q->whereNull('utilisations_max')
                      ->orWhereColumn('utilisations_actuelles', '<', 'utilisations_max');
                })
                ->get();

            // 3. Pour chaque promotion active, appliquer aux plats disponibles qui n'ont pas déjà cette promotion
            foreach ($promotionsActives as $promotion) {
                // Récupérer les IDs des plats qui ont déjà cette promotion publiée
                $platsDejaEnPromo = PromoMenuItem::where('id_promo', $promotion->id_promo)
                    ->pluck('id_menuitem')
                    ->toArray();
                
                // Récupérer tous les plats disponibles qui n'ont pas déjà cette promotion
                $platsDisponibles = MenuItem::with('categorie')
                    ->where('statut_disponibilite', true)
                    ->whereNotIn('id_menuitem', $platsDejaEnPromo)
                    ->get();
                
                // Appliquer la promotion à chaque plat disponible
                foreach ($platsDisponibles as $plat) {
                    // Calculer le prix promotionnel selon le type de promotion
                    $prixOriginal = (float) $plat->prix;
                    $prixPromo = $prixOriginal;
                    
                    switch ($promotion->type_promotion) {
                        case 'pourcentage':
                            $prixPromo = $prixOriginal * (1 - ($promotion->valeur / 100));
                            break;
                        case 'montant_fixe':
                            $prixPromo = max(0, $prixOriginal - $promotion->valeur);
                            break;
                        case 'offre_speciale':
                        case 'menu_special':
                            // Pour les offres spéciales, on applique une réduction par défaut de 10%
                            $prixPromo = $prixOriginal * 0.9;
                            break;
                    }
                    
                    $reduction = $prixOriginal - $prixPromo;
                    $pourcentageReduction = $prixOriginal > 0 ? round(($reduction / $prixOriginal) * 100, 2) : 0;
                    
                    // Vérifier si ce plat n'est pas déjà dans la liste (éviter les doublons)
                    $existeDeja = $platsData->contains(function ($item) use ($plat, $promotion) {
                        return $item['id_menuitem'] == $plat->id_menuitem && 
                               $item['promotion']['id_promo'] == $promotion->id_promo;
                    });
                    
                    if (!$existeDeja && $prixPromo < $prixOriginal) {
                        $platsData->push([
                            'id_menuitem' => $plat->id_menuitem,
                            'nom' => $plat->nom,
                            'description' => $plat->description ?? '',
                            'prix_original' => $prixOriginal,
                            'prix_promotionnel' => round($prixPromo, 2),
                            'reduction' => round($reduction, 2),
                            'pourcentage_reduction' => $pourcentageReduction,
                            'photo_url' => $plat->photo_url,
                            'categorie' => $plat->categorie ? [
                                'id_categorie' => $plat->categorie->id_categorie,
                                'nom' => $plat->categorie->nom,
                            ] : null,
                            'promotion' => [
                                'id_promo' => $promotion->id_promo,
                                'titre' => $promotion->titre,
                                'type_promotion' => $promotion->type_promotion,
                                'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                            ],
                        ]);
                    }
                }
            }

            // Éviter les doublons : si un plat a plusieurs promotions, garder celle avec la meilleure réduction
            $platsUniques = $platsData->groupBy('id_menuitem')->map(function ($group) {
                // Garder le plat avec la meilleure réduction (prix promotionnel le plus bas)
                return $group->sortBy('prix_promotionnel')->first();
            })->values();

            \Log::info('Plats finaux retournés par /api/promotions/plats', [
                'total' => $platsUniques->count(),
                'plats' => $platsUniques->map(function ($p) {
                    return [
                        'id_menuitem' => $p['id_menuitem'] ?? null,
                        'nom' => $p['nom'] ?? null,
                        'prix_original' => $p['prix_original'] ?? null,
                        'prix_promotionnel' => $p['prix_promotionnel'] ?? null,
                        'promotion_id' => $p['promotion']['id_promo'] ?? null,
                    ];
                })->toArray(),
            ]);

            return $this->successResponse([
                'plats' => $platsUniques,
                'total' => $platsUniques->count(),
            ], 'Plats en promotion récupérés avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des plats en promotion', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des plats en promotion',
                []
            );
        }
    }

    /**
     * Vérifier et valider un code promo
     * 
     * POST /api/promotions/verifier-code
     * 
     * Body:
     * {
     *   "code_promo": "PROMO2024",
     *   "montant_panier": 50.00  // Optionnel : pour vérifier le minimum
     * }
     */
    public function verifierCode(Request $request)
    {
        try {
            $request->validate([
                'code_promo' => 'required|string|max:50',
                'montant_panier' => 'nullable|numeric|min:0',
            ]);

            $codePromo = strtoupper(trim($request->input('code_promo')));
            $montantPanier = $request->input('montant_panier', 0);

            // Chercher la promotion par code
            $promotion = Promotion::where('code_promo', $codePromo)->first();

            if (!$promotion) {
                return $this->errorResponse('Code promo invalide', 404);
            }

            // Vérifier si la promotion est valide
            if (!$promotion->estValide()) {
                $message = 'Cette promotion n\'est plus valide';
                if ($promotion->statut === 'expiree' || now()->gt($promotion->date_fin)) {
                    $message = 'Cette promotion a expiré';
                } elseif ($promotion->statut === 'epuisee' || 
                    ($promotion->utilisations_max && $promotion->utilisations_actuelles >= $promotion->utilisations_max)) {
                    $message = 'Cette promotion est épuisée';
                }
                return $this->errorResponse($message, 400);
            }

            // Vérifier le minimum du panier
            if ($montantPanier > 0 && $montantPanier < $promotion->valeur_minimum_panier) {
                return $this->errorResponse(
                    "Le montant minimum du panier pour cette promotion est de " . number_format($promotion->valeur_minimum_panier * 2000, 0, ',', ' ') . " CDF",
                    400
                );
            }

            // Calculer la réduction potentielle
            $reduction = $montantPanier > 0 ? $promotion->calculerReduction($montantPanier) : 0;

            // Formater la réponse
            $promotionData = [
                'id_promo' => $promotion->id_promo,
                'titre' => $promotion->titre,
                'type_promotion' => $promotion->type_promotion,
                'valeur' => (float) $promotion->valeur,
                'valeur_minimum_panier' => (float) $promotion->valeur_minimum_panier,
                'date_fin' => $promotion->date_fin ? $promotion->date_fin->format('Y-m-d H:i:s') : null,
                'reduction_calculee' => (float) $reduction,
                'utilisations_restantes' => $promotion->utilisations_max 
                    ? $promotion->utilisations_max - $promotion->utilisations_actuelles 
                    : null,
            ];

            return $this->successResponse($promotionData, 'Code promo valide');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Données invalides', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la vérification du code promo',
                []
            );
        }
    }

    /**
     * Route de debug pour voir toutes les promotions publiées
     * GET /api/promotions/plats/debug
     */
    public function platsDebug(Request $request)
    {
        try {
            // Récupérer toutes les promotions publiées sans aucun filtre
            $platsEnPromo = PromoMenuItem::with(['menuItem.categorie', 'promotion'])->get();
            
            $debugData = [
                'total_promo_menu_item' => $platsEnPromo->count(),
                'promo_menu_items' => $platsEnPromo->map(function ($p) {
                    return [
                        'id_promomenuitem' => $p->id_promomenuitem,
                        'id_promo' => $p->id_promo,
                        'id_menuitem' => $p->id_menuitem,
                        'prix_promotionnel' => $p->prix_promotionnel,
                        'statut' => $p->statut,
                        'date_debut' => $p->date_debut,
                        'date_fin' => $p->date_fin,
                        'has_menuItem' => $p->menuItem ? true : false,
                        'has_promotion' => $p->promotion ? true : false,
                        'menuItem' => $p->menuItem ? [
                            'id_menuitem' => $p->menuItem->id_menuitem,
                            'nom' => $p->menuItem->nom,
                            'statut_disponibilite' => $p->menuItem->statut_disponibilite,
                            'prix' => $p->menuItem->prix,
                        ] : null,
                        'promotion' => $p->promotion ? [
                            'id_promo' => $p->promotion->id_promo,
                            'titre' => $p->promotion->titre,
                            'statut' => $p->promotion->statut,
                            'date_debut' => $p->promotion->date_debut ? $p->promotion->date_debut->format('Y-m-d H:i:s') : null,
                            'date_fin' => $p->promotion->date_fin ? $p->promotion->date_fin->format('Y-m-d H:i:s') : null,
                            'utilisations_actuelles' => $p->promotion->utilisations_actuelles,
                            'utilisations_max' => $p->promotion->utilisations_max,
                        ] : null,
                    ];
                })->toArray(),
            ];
            
            return $this->successResponse($debugData, 'Données de debug récupérées');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des données de debug',
                []
            );
        }
    }
}
