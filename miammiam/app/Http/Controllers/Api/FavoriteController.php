<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToFavoritesRequest;
use App\Models\Favori;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Lister tous les favoris de l'utilisateur connecté
     * 
     * GET /api/favorites
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer tous les favoris de l'utilisateur avec les informations des plats
            $favoris = Favori::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->with(['menuItem.categorie']) // Charger le plat et sa catégorie
                ->orderBy('date_ajout', 'desc') // Plus récents en premier
                ->get();
            
            // Formater la réponse
            $favorisFormates = $favoris->map(function ($favori) {
                return [
                    'id_favori' => $favori->id_favori,
                    'menu_item' => [
                        'id_menuitem' => $favori->menuItem->id_menuitem ?? null,
                        'nom' => $favori->menuItem->nom ?? 'Plat supprimé',
                        'description' => $favori->menuItem->description ?? null,
                        'prix' => $favori->menuItem->prix ?? null,
                        'photo_url' => $favori->menuItem->photo_url ?? null,
                        'statut_disponibilite' => $favori->menuItem->statut_disponibilite ?? false,
                        'categorie' => [
                            'id_categorie' => $favori->menuItem->categorie->id_categorie ?? null,
                            'nom' => $favori->menuItem->categorie->nom ?? null,
                        ],
                    ],
                    'date_ajout' => $favori->date_ajout->format('Y-m-d H:i:s'),
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
            \Log::error('Erreur lors de la récupération des favoris', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des favoris',
                'error' => $e->getMessage(),
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Ce plat n\'est pas disponible',
                ], 404);
            }

            // Vérifier si le favori existe déjà
            $favoriExistant = Favori::where('id_utilisateur', $utilisateur->id_utilisateur)
                                   ->where('id_menuitem', $data['menu_item_id'])
                                   ->first();

            if ($favoriExistant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce plat est déjà dans vos favoris',
                ], 400);
            }

            // Créer le favori
            $favori = Favori::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'id_menuitem' => $data['menu_item_id'],
            ]);

            // Charger les relations pour la réponse
            $favori->load(['menuItem.categorie']);

            return response()->json([
                'success' => true,
                'message' => 'Plat ajouté aux favoris avec succès',
                'data' => [
                    'id_favori' => $favori->id_favori,
                    'menu_item' => [
                        'id_menuitem' => $favori->menuItem->id_menuitem,
                        'nom' => $favori->menuItem->nom,
                        'description' => $favori->menuItem->description,
                        'prix' => $favori->menuItem->prix,
                        'photo_url' => $favori->menuItem->photo_url,
                        'categorie' => [
                            'id_categorie' => $favori->menuItem->categorie->id_categorie,
                            'nom' => $favori->menuItem->categorie->nom,
                        ],
                    ],
                    'date_ajout' => $favori->date_ajout->format('Y-m-d H:i:s'),
                ],
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'ajout du favori', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du favori',
                'error' => $e->getMessage(),
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Favori non trouvé',
                ], 404);
            }
            
            // Vérifier que le favori appartient à l'utilisateur connecté (sécurité)
            if ($favori->id_utilisateur !== $utilisateur->id_utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à retirer ce favori',
                ], 403);
            }
            
            // Supprimer le favori
            $favori->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Favori retiré avec succès',
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du favori', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du favori',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}