<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Requests\ModerateReviewRequest;
use App\Models\Avis;
use App\Models\Commande;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class ReviewController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Créer un avis sur un plat ou le service
     * 
     * POST /api/reviews
     */
    public function store(StoreReviewRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            // Vérifier que l'utilisateur est authentifié
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }

            // La commande est optionnelle : si fournie, elle doit exister et appartenir à l'utilisateur
            // Sinon, on crée l'avis sans commande (c'est autorisé)
            
            // Normaliser id_commande : si non fourni ou null/vide, mettre à null
            if (!isset($data['id_commande']) || $data['id_commande'] === null || $data['id_commande'] === '') {
                $data['id_commande'] = null;
            }
            
            // Si une commande est fournie, vérifier qu'elle existe et appartient à l'utilisateur
            if ($data['id_commande'] !== null) {
                $commande = Commande::where('id_commande', $data['id_commande'])
                    ->where('id_utilisateur', $utilisateur->id_utilisateur)
                    ->first();

                if (!$commande) {
                    \Log::info('Commande non trouvée ou n\'appartient pas à l\'utilisateur', [
                        'id_commande' => $data['id_commande'],
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                    ]);
                    return $this->errorResponse('Cette commande n\'existe pas ou ne vous appartient pas', 403);
                }
            }

            // Si type_avis = 'plat', vérifier que le plat existe
            if ($data['type_avis'] === 'plat') {
                if (!isset($data['id_menuitem'])) {
                    return $this->errorResponse('L\'identifiant du plat est obligatoire pour un avis sur un plat', 400);
                }

                $menuItem = MenuItem::find($data['id_menuitem']);
                if (!$menuItem) {
                    return $this->errorResponse('Ce plat n\'existe pas', 404);
                }
            } else {
                // Pour un avis sur le service, id_menuitem doit être null
                $data['id_menuitem'] = null;
            }

            // Créer l'avis
            $avis = Avis::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'id_menuitem' => $data['id_menuitem'] ?? null,
                'id_commande' => $data['id_commande'] ?? null,
                'type_avis' => $data['type_avis'],
                'note' => $data['note'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                ],
                'menu_item' => $avis->menuItem ? [
                    'id_menuitem' => $avis->menuItem->id_menuitem,
                    'nom' => $avis->menuItem->nom,
                ] : null,
                'commande' => $avis->commande ? [
                    'id_commande' => $avis->commande->id_commande,
                    'numero_commande' => $avis->commande->numero_commande,
                ] : null,
            ];

            return $this->createdResponse($avisData, 'Avis créé avec succès');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la création de l\'avis',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'type_avis' => $data['type_avis'] ?? null,
                ]
            );
        }
    }

    /**
     * Lister tous les avis avec filtrage et tri (pour gérant/admin)
     * 
     * GET /api/reviews
     * 
     * Query parameters:
     * - statut_moderation: approuve, en_attente, rejete (optionnel)
     * - type_avis: plat, service (optionnel)
     * - note_min: note minimale (optionnel)
     * - note_max: note maximale (optionnel)
     * - avec_reponse: true/false (optionnel)
     * - sort_by: date_creation, note, date_reponse (défaut: date_creation)
     * - sort_order: asc, desc (défaut: desc)
     * - limit: nombre d'éléments par page (défaut: 20)
     * - offset: offset pour la pagination (défaut: 0)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent consulter les avis.', 403);
            }

            // Construire la requête avec les relations
            $query = Avis::with(['utilisateur', 'menuItem', 'commande']);

            // Filtrage par statut de modération
            if ($request->has('statut_moderation')) {
                $statut = $request->input('statut_moderation');
                if (in_array($statut, ['approuve', 'en_attente', 'rejete'])) {
                    $query->where('statut_moderation', $statut);
                }
            }

            // Filtrage par type d'avis
            if ($request->has('type_avis')) {
                $type = $request->input('type_avis');
                if (in_array($type, ['plat', 'service'])) {
                    $query->where('type_avis', $type);
                }
            }

            // Filtrage par note minimale
            if ($request->has('note_min')) {
                $noteMin = (int) $request->input('note_min');
                if ($noteMin >= 1 && $noteMin <= 5) {
                    $query->where('note', '>=', $noteMin);
                }
            }

            // Filtrage par note maximale
            if ($request->has('note_max')) {
                $noteMax = (int) $request->input('note_max');
                if ($noteMax >= 1 && $noteMax <= 5) {
                    $query->where('note', '<=', $noteMax);
                }
            }

            // Filtrage par présence de réponse
            if ($request->has('avec_reponse')) {
                $avecReponse = filter_var($request->input('avec_reponse'), FILTER_VALIDATE_BOOLEAN);
                if ($avecReponse) {
                    $query->whereNotNull('reponse_gerant');
                } else {
                    $query->whereNull('reponse_gerant');
                }
            }

            // Tri
            $sortBy = $request->input('sort_by', 'date_creation');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSortFields = ['date_creation', 'note', 'date_reponse'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date_creation';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = (int) $request->input('limit', 20);
            $offset = (int) $request->input('offset', 0);
            
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            // Compter le total avant pagination
            $total = $query->count();

            // Appliquer la pagination
            $avis = $query->limit($limit)->offset($offset)->get();

            // Formater les données pour la réponse
            $avisData = $avis->map(function ($avis) {
                return [
                    'id_avis' => $avis->id_avis,
                    'type_avis' => $avis->type_avis,
                    'note' => $avis->note,
                    'commentaire' => $avis->commentaire,
                    'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                    'statut_moderation' => $avis->statut_moderation,
                    'reponse_gerant' => $avis->reponse_gerant,
                    'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                    'utilisateur' => [
                        'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                        'nom' => $avis->utilisateur->nom,
                        'prenom' => $avis->utilisateur->prenom,
                        'email' => $avis->utilisateur->email,
                    ],
                    'menu_item' => $avis->menuItem ? [
                        'id_menuitem' => $avis->menuItem->id_menuitem,
                        'nom' => $avis->menuItem->nom,
                    ] : null,
                    'commande' => $avis->commande ? [
                        'id_commande' => $avis->commande->id_commande,
                        'numero_commande' => $avis->commande->numero_commande,
                    ] : null,
                ];
            });

            return $this->successResponse([
                'avis' => $avisData,
                'meta' => [
                    'total' => $total,
                    'count' => $avis->count(),
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                ],
            ], 'Avis récupérés avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des avis',
                []
            );
        }
    }

    /**
     * Afficher un avis spécifique (pour gérant/admin)
     * 
     * GET /api/reviews/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent consulter les avis.', 403);
            }

            $avis = Avis::with(['utilisateur', 'menuItem', 'commande'])
                ->find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                    'email' => $avis->utilisateur->email,
                ],
                'menu_item' => $avis->menuItem ? [
                    'id_menuitem' => $avis->menuItem->id_menuitem,
                    'nom' => $avis->menuItem->nom,
                    'description' => $avis->menuItem->description,
                ] : null,
                'commande' => $avis->commande ? [
                    'id_commande' => $avis->commande->id_commande,
                    'numero_commande' => $avis->commande->numero_commande,
                    'statut' => $avis->commande->statut,
                ] : null,
            ];

            return $this->successResponse($avisData, 'Avis récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'avis',
                ['id_avis' => $id]
            );
        }
    }

    /**
     * Répondre à un avis (pour gérant/admin)
     * 
     * PUT /api/reviews/{id}
     */
    public function update(UpdateReviewRequest $request, $id)
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Mettre à jour la réponse et la date de réponse
            $avis->reponse_gerant = $data['reponse_gerant'];
            $avis->date_reponse = now();
            $avis->save();

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                ],
            ];

            return $this->successResponse($avisData, 'Réponse ajoutée avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la mise à jour de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Modérer un avis (changer le statut) (pour gérant/admin)
     * 
     * PUT /api/reviews/{id}/moderate
     */
    public function moderate(ModerateReviewRequest $request, $id)
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Mettre à jour le statut de modération
            $avis->statut_moderation = $data['statut_moderation'];
            $avis->save();

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
            ];

            $statutLabel = [
                'approuve' => 'approuvé',
                'en_attente' => 'en attente',
                'rejete' => 'rejeté',
            ];

            return $this->successResponse($avisData, 'Avis ' . ($statutLabel[$avis->statut_moderation] ?? $avis->statut_moderation) . ' avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la modération de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Supprimer un avis (pour gérant/admin)
     * 
     * DELETE /api/reviews/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent supprimer les avis.', 403);
            }

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            $avis->delete();

            return $this->successResponse(null, 'Avis supprimé avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la suppression de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }
}

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class ReviewController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Créer un avis sur un plat ou le service
     * 
     * POST /api/reviews
     */
    public function store(StoreReviewRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            // Vérifier que l'utilisateur est authentifié
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }

            // La commande est optionnelle : si fournie, elle doit exister et appartenir à l'utilisateur
            // Sinon, on crée l'avis sans commande (c'est autorisé)
            
            // Normaliser id_commande : si non fourni ou null/vide, mettre à null
            if (!isset($data['id_commande']) || $data['id_commande'] === null || $data['id_commande'] === '') {
                $data['id_commande'] = null;
            }
            
            // Si une commande est fournie, vérifier qu'elle existe et appartient à l'utilisateur
            if ($data['id_commande'] !== null) {
                $commande = Commande::where('id_commande', $data['id_commande'])
                    ->where('id_utilisateur', $utilisateur->id_utilisateur)
                    ->first();

                if (!$commande) {
                    \Log::info('Commande non trouvée ou n\'appartient pas à l\'utilisateur', [
                        'id_commande' => $data['id_commande'],
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                    ]);
                    return $this->errorResponse('Cette commande n\'existe pas ou ne vous appartient pas', 403);
                }
            }

            // Si type_avis = 'plat', vérifier que le plat existe
            if ($data['type_avis'] === 'plat') {
                if (!isset($data['id_menuitem'])) {
                    return $this->errorResponse('L\'identifiant du plat est obligatoire pour un avis sur un plat', 400);
                }

                $menuItem = MenuItem::find($data['id_menuitem']);
                if (!$menuItem) {
                    return $this->errorResponse('Ce plat n\'existe pas', 404);
                }
            } else {
                // Pour un avis sur le service, id_menuitem doit être null
                $data['id_menuitem'] = null;
            }

            // Créer l'avis
            $avis = Avis::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'id_menuitem' => $data['id_menuitem'] ?? null,
                'id_commande' => $data['id_commande'] ?? null,
                'type_avis' => $data['type_avis'],
                'note' => $data['note'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                ],
                'menu_item' => $avis->menuItem ? [
                    'id_menuitem' => $avis->menuItem->id_menuitem,
                    'nom' => $avis->menuItem->nom,
                ] : null,
                'commande' => $avis->commande ? [
                    'id_commande' => $avis->commande->id_commande,
                    'numero_commande' => $avis->commande->numero_commande,
                ] : null,
            ];

            return $this->createdResponse($avisData, 'Avis créé avec succès');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la création de l\'avis',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'type_avis' => $data['type_avis'] ?? null,
                ]
            );
        }
    }

    /**
     * Lister tous les avis avec filtrage et tri (pour gérant/admin)
     * 
     * GET /api/reviews
     * 
     * Query parameters:
     * - statut_moderation: approuve, en_attente, rejete (optionnel)
     * - type_avis: plat, service (optionnel)
     * - note_min: note minimale (optionnel)
     * - note_max: note maximale (optionnel)
     * - avec_reponse: true/false (optionnel)
     * - sort_by: date_creation, note, date_reponse (défaut: date_creation)
     * - sort_order: asc, desc (défaut: desc)
     * - limit: nombre d'éléments par page (défaut: 20)
     * - offset: offset pour la pagination (défaut: 0)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent consulter les avis.', 403);
            }

            // Construire la requête avec les relations
            $query = Avis::with(['utilisateur', 'menuItem', 'commande']);

            // Filtrage par statut de modération
            if ($request->has('statut_moderation')) {
                $statut = $request->input('statut_moderation');
                if (in_array($statut, ['approuve', 'en_attente', 'rejete'])) {
                    $query->where('statut_moderation', $statut);
                }
            }

            // Filtrage par type d'avis
            if ($request->has('type_avis')) {
                $type = $request->input('type_avis');
                if (in_array($type, ['plat', 'service'])) {
                    $query->where('type_avis', $type);
                }
            }

            // Filtrage par note minimale
            if ($request->has('note_min')) {
                $noteMin = (int) $request->input('note_min');
                if ($noteMin >= 1 && $noteMin <= 5) {
                    $query->where('note', '>=', $noteMin);
                }
            }

            // Filtrage par note maximale
            if ($request->has('note_max')) {
                $noteMax = (int) $request->input('note_max');
                if ($noteMax >= 1 && $noteMax <= 5) {
                    $query->where('note', '<=', $noteMax);
                }
            }

            // Filtrage par présence de réponse
            if ($request->has('avec_reponse')) {
                $avecReponse = filter_var($request->input('avec_reponse'), FILTER_VALIDATE_BOOLEAN);
                if ($avecReponse) {
                    $query->whereNotNull('reponse_gerant');
                } else {
                    $query->whereNull('reponse_gerant');
                }
            }

            // Tri
            $sortBy = $request->input('sort_by', 'date_creation');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSortFields = ['date_creation', 'note', 'date_reponse'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date_creation';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = (int) $request->input('limit', 20);
            $offset = (int) $request->input('offset', 0);
            
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            // Compter le total avant pagination
            $total = $query->count();

            // Appliquer la pagination
            $avis = $query->limit($limit)->offset($offset)->get();

            // Formater les données pour la réponse
            $avisData = $avis->map(function ($avis) {
                return [
                    'id_avis' => $avis->id_avis,
                    'type_avis' => $avis->type_avis,
                    'note' => $avis->note,
                    'commentaire' => $avis->commentaire,
                    'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                    'statut_moderation' => $avis->statut_moderation,
                    'reponse_gerant' => $avis->reponse_gerant,
                    'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                    'utilisateur' => [
                        'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                        'nom' => $avis->utilisateur->nom,
                        'prenom' => $avis->utilisateur->prenom,
                        'email' => $avis->utilisateur->email,
                    ],
                    'menu_item' => $avis->menuItem ? [
                        'id_menuitem' => $avis->menuItem->id_menuitem,
                        'nom' => $avis->menuItem->nom,
                    ] : null,
                    'commande' => $avis->commande ? [
                        'id_commande' => $avis->commande->id_commande,
                        'numero_commande' => $avis->commande->numero_commande,
                    ] : null,
                ];
            });

            return $this->successResponse([
                'avis' => $avisData,
                'meta' => [
                    'total' => $total,
                    'count' => $avis->count(),
                    'offset' => $offset,
                    'limit' => $limit,
                    'has_more' => ($offset + $limit) < $total,
                ],
            ], 'Avis récupérés avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des avis',
                []
            );
        }
    }

    /**
     * Afficher un avis spécifique (pour gérant/admin)
     * 
     * GET /api/reviews/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent consulter les avis.', 403);
            }

            $avis = Avis::with(['utilisateur', 'menuItem', 'commande'])
                ->find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                    'email' => $avis->utilisateur->email,
                ],
                'menu_item' => $avis->menuItem ? [
                    'id_menuitem' => $avis->menuItem->id_menuitem,
                    'nom' => $avis->menuItem->nom,
                    'description' => $avis->menuItem->description,
                ] : null,
                'commande' => $avis->commande ? [
                    'id_commande' => $avis->commande->id_commande,
                    'numero_commande' => $avis->commande->numero_commande,
                    'statut' => $avis->commande->statut,
                ] : null,
            ];

            return $this->successResponse($avisData, 'Avis récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'avis',
                ['id_avis' => $id]
            );
        }
    }

    /**
     * Répondre à un avis (pour gérant/admin)
     * 
     * PUT /api/reviews/{id}
     */
    public function update(UpdateReviewRequest $request, $id)
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Mettre à jour la réponse et la date de réponse
            $avis->reponse_gerant = $data['reponse_gerant'];
            $avis->date_reponse = now();
            $avis->save();

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
                'utilisateur' => [
                    'id_utilisateur' => $avis->utilisateur->id_utilisateur,
                    'nom' => $avis->utilisateur->nom,
                    'prenom' => $avis->utilisateur->prenom,
                ],
            ];

            return $this->successResponse($avisData, 'Réponse ajoutée avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la mise à jour de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Modérer un avis (changer le statut) (pour gérant/admin)
     * 
     * PUT /api/reviews/{id}/moderate
     */
    public function moderate(ModerateReviewRequest $request, $id)
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            // Mettre à jour le statut de modération
            $avis->statut_moderation = $data['statut_moderation'];
            $avis->save();

            // Charger les relations pour la réponse
            $avis->load(['utilisateur', 'menuItem', 'commande']);

            // Formater les données pour la réponse
            $avisData = [
                'id_avis' => $avis->id_avis,
                'type_avis' => $avis->type_avis,
                'note' => $avis->note,
                'commentaire' => $avis->commentaire,
                'date_creation' => $avis->date_creation ? $avis->date_creation->format('Y-m-d H:i:s') : null,
                'statut_moderation' => $avis->statut_moderation,
                'reponse_gerant' => $avis->reponse_gerant,
                'date_reponse' => $avis->date_reponse ? $avis->date_reponse->format('Y-m-d H:i:s') : null,
            ];

            $statutLabel = [
                'approuve' => 'approuvé',
                'en_attente' => 'en attente',
                'rejete' => 'rejeté',
            ];

            return $this->successResponse($avisData, 'Avis ' . ($statutLabel[$avis->statut_moderation] ?? $avis->statut_moderation) . ' avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la modération de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Supprimer un avis (pour gérant/admin)
     * 
     * DELETE /api/reviews/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un gérant ou admin
            if (!$user || !in_array($user->role, ['gerant', 'admin'])) {
                return $this->errorResponse('Accès non autorisé. Seuls les gérants et admins peuvent supprimer les avis.', 403);
            }

            $avis = Avis::find($id);

            if (!$avis) {
                return $this->errorResponse('Avis non trouvé', 404);
            }

            $avis->delete();

            return $this->successResponse(null, 'Avis supprimé avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la suppression de l\'avis',
                [
                    'id_avis' => $id,
                    'user_id' => $user->id_utilisateur ?? null,
                ]
            );
        }
    }
}
