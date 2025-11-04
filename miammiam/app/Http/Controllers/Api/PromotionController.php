<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Models\Promotion;
use App\Models\PromoMenuItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

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
     */
    public function plats(Request $request)
    {
        try {
            // Récupérer les plats en promotion actifs
            $platsEnPromo = PromoMenuItem::with(['menuItem.categorie', 'promotion'])
                ->active()
                ->get();

            // Filtrer pour ne garder que ceux dont la promotion principale est aussi active
            $platsValides = $platsEnPromo->filter(function ($promoPlat) {
                return $promoPlat->promotion && $promoPlat->promotion->estValide();
            });

            // Formater les données pour la réponse
            $platsData = $platsValides->map(function ($promoPlat) {
                $plat = $promoPlat->menuItem;
                $promotion = $promoPlat->promotion;
                
                return [
                    'id_menuitem' => $plat->id_menuitem,
                    'nom' => $plat->nom,
                    'description' => $plat->description,
                    'prix_original' => (float) $plat->prix,
                    'prix_promotionnel' => (float) $promoPlat->prix_promotionnel,
                    'reduction' => (float) $plat->prix - (float) $promoPlat->prix_promotionnel,
                    'pourcentage_reduction' => round((((float) $plat->prix - (float) $promoPlat->prix_promotionnel) / (float) $plat->prix) * 100, 2),
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

            return $this->successResponse([
                'plats' => $platsData,
                'total' => $platsData->count(),
            ], 'Plats en promotion récupérés avec succès');

        } catch (\Exception $e) {
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
}

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
     */
    public function plats(Request $request)
    {
        try {
            // Récupérer les plats en promotion actifs
            $platsEnPromo = PromoMenuItem::with(['menuItem.categorie', 'promotion'])
                ->active()
                ->get();

            // Filtrer pour ne garder que ceux dont la promotion principale est aussi active
            $platsValides = $platsEnPromo->filter(function ($promoPlat) {
                return $promoPlat->promotion && $promoPlat->promotion->estValide();
            });

            // Formater les données pour la réponse
            $platsData = $platsValides->map(function ($promoPlat) {
                $plat = $promoPlat->menuItem;
                $promotion = $promoPlat->promotion;
                
                return [
                    'id_menuitem' => $plat->id_menuitem,
                    'nom' => $plat->nom,
                    'description' => $plat->description,
                    'prix_original' => (float) $plat->prix,
                    'prix_promotionnel' => (float) $promoPlat->prix_promotionnel,
                    'reduction' => (float) $plat->prix - (float) $promoPlat->prix_promotionnel,
                    'pourcentage_reduction' => round((((float) $plat->prix - (float) $promoPlat->prix_promotionnel) / (float) $plat->prix) * 100, 2),
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

            return $this->successResponse([
                'plats' => $platsData,
                'total' => $platsData->count(),
            ], 'Plats en promotion récupérés avec succès');

        } catch (\Exception $e) {
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
}
