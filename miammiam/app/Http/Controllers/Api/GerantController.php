<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

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
