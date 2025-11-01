<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\AddToFavoritesRequest;
use App\Models\Favori;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class FavoriteController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Lister tous les favoris de l'utilisateur connecté
     * 
     * GET /api/favorites
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Vérifier que l'utilisateur est bien authentifié
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Récupérer tous les favoris de l'utilisateur avec les informations des plats
            $favoris = Favori::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->with(['menuItem.categorie']) // Charger le plat et sa catégorie
                ->orderBy('date_ajout', 'desc') // Plus récents en premier
                ->get();
            
            // Formater la réponse
            $favorisFormates = $favoris->map(function ($favori) {
                // Vérifier si menuItem existe (au cas où le plat aurait été supprimé)
                $menuItem = $favori->menuItem;
                
                return [
                    'id_favori' => $favori->id_favori,
                    'menu_item' => $menuItem ? [
                        'id_menuitem' => $menuItem->id_menuitem,
                        'nom' => $menuItem->nom,
                        'description' => $menuItem->description,
                        'prix' => $menuItem->prix,
                        'photo_url' => $menuItem->photo_url,
                        'statut_disponibilite' => $menuItem->statut_disponibilite,
                        'categorie' => $menuItem->categorie ? [
                            'id_categorie' => $menuItem->categorie->id_categorie,
                            'nom' => $menuItem->categorie->nom,
                        ] : null,
                    ] : null,
                    'date_ajout' => $favori->date_ajout ? $favori->date_ajout->format('Y-m-d H:i:s') : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $favorisFormates,
                'meta' => [
                    'total' => $favorisFormates->count(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des favoris',
                ['user_id' => $utilisateur->id_utilisateur ?? null]
            );
        }
    }

    /**
     * Ajouter un plat aux favoris
     * 
     * POST /api/favorites
     */
    public function store(AddToFavoritesRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();
            
            // Vérifier que le plat existe et est disponible
            $menuItem = MenuItem::where('id_menuitem', $data['menu_item_id'])
                               ->where('statut_disponibilite', true)
                               ->first();

            if (!$menuItem) {
                return $this->notFoundResponse('Plat');
            }

            // Vérifier si le favori existe déjà
            $favoriExistant = Favori::where('id_utilisateur', $utilisateur->id_utilisateur)
                                   ->where('id_menuitem', $data['menu_item_id'])
                                   ->first();

            if ($favoriExistant) {
                return $this->errorResponse('Ce plat est déjà dans vos favoris', 400);
            }

            // Créer le favori
            $favori = Favori::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'id_menuitem' => $data['menu_item_id'],
            ]);

            // Charger les relations pour la réponse
            $favori->load(['menuItem.categorie']);

            // Formater les données pour la réponse
            $menuItem = $favori->menuItem;
            $favoriData = [
                'id_favori' => $favori->id_favori,
                'menu_item' => $menuItem ? [
                    'id_menuitem' => $menuItem->id_menuitem,
                    'nom' => $menuItem->nom,
                    'description' => $menuItem->description,
                    'prix' => $menuItem->prix,
                    'photo_url' => $menuItem->photo_url,
                    'categorie' => $menuItem->categorie ? [
                        'id_categorie' => $menuItem->categorie->id_categorie,
                        'nom' => $menuItem->categorie->nom,
                    ] : null,
                ] : null,
                'date_ajout' => $favori->date_ajout ? $favori->date_ajout->format('Y-m-d H:i:s') : null,
            ];

            return $this->createdResponse($favoriData, 'Plat ajouté aux favoris avec succès');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de l\'ajout du favori',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'menu_item_id' => $data['menu_item_id'] ?? null,
                ]
            );
        }
    }

    /**
     * Retirer un favori
     * 
     * DELETE /api/favorites/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer le favori
            $favori = Favori::find($id);
            
            // Vérifier que le favori existe
            if (!$favori) {
                return $this->notFoundResponse('Favori');
            }
            
            // Vérifier que le favori appartient à l'utilisateur connecté (sécurité)
            if ($favori->id_utilisateur !== $utilisateur->id_utilisateur) {
                return $this->unauthorizedResponse('Vous n\'êtes pas autorisé à retirer ce favori');
            }
            
            // Supprimer le favori
            $favori->delete();
            
            return $this->successResponse(null, 'Favori retiré avec succès', 200);
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la suppression du favori',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'favori_id' => $id ?? null,
                ]
            );
        }
    }
}