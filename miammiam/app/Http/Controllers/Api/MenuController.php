<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    // Consulter le menu avec filtres (public - tous les utilisateurs)
    public function index(Request $request)
    {
        try {
            $categorieId = $request->query('categorie');
            $search = $request->query('search');
            $disponible = $request->query('disponible', true);
            
            // Construire une clé de cache unique en fonction des filtres
            $cacheKey = 'menu_' . md5(json_encode([
                'categorie' => $categorieId,
                'search' => $search,
                'disponible' => $disponible
            ]));
            
            // Cache pendant 60 minutes
            $menuItems = Cache::remember($cacheKey, 3600, function () use ($categorieId, $search, $disponible) {
                $query = MenuItem::with('categorie')
                                 ->where('statut_disponibilite', $disponible);
                
                // Filtre par catégorie
                if ($categorieId) {
                    $query->where('id_categorie', $categorieId);
                }
                
                // Filtre par recherche (nom ou description)
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nom', 'ILIKE', "%{$search}%")
                          ->orWhere('description', 'ILIKE', "%{$search}%")
                          ->orWhere('ingredients', 'ILIKE', "%{$search}%");
                    });
                }
                
                return $query->orderBy('id_categorie')
                            ->orderBy('nom')
                            ->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $menuItems,
                'filtres' => [
                    'categorie' => $categorieId,
                    'search' => $search,
                    'disponible' => $disponible,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Consulter un plat spécifique (public)
    public function show($id)
    {
        try {
            $menuItem = MenuItem::with('categorie')
                                ->where('statut_disponibilite', true)
                                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $menuItem,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé ou non disponible',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Plats du jour (public)
    public function platsJour()
    {
        try {
            // Cache des plats du jour pendant 30 minutes
            $platsJour = Cache::remember('plats_du_jour', 1800, function () {
                return MenuItem::with('categorie')
                              ->where('plat_du_jour', true)
                              ->where('statut_disponibilite', true)
                              ->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $platsJour,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plats du jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Liste des catégories (public)
    public function categories()
    {
        try {
            // Cache des catégories pendant 24 heures (changent rarement)
            $categories = Cache::remember('categories_list', 86400, function () {
                return Categorie::orderBy('nom')->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $categories,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter la liste des plats disponibles (pour les employés)
     * 
     * GET /api/menu/available
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Retourne uniquement les plats disponibles (statut_disponibilite = true)
     * Format optimisé pour un affichage rapide
     */
    public function available(Request $request)
    {
        try {
            // Paramètre optionnel pour filtrer par catégorie
            $categorieId = $request->query('categorie');
            
            // Requête de base : uniquement les plats disponibles
            $query = MenuItem::with('categorie')
                ->where('statut_disponibilite', true);
            
            // Filtre par catégorie si fourni
            if ($categorieId) {
                $query->where('id_categorie', $categorieId);
            }
            
            // Trier par catégorie puis par nom
            $menuItems = $query->orderBy('id_categorie')
                ->orderBy('nom')
                ->get();
            
            // Formater les données pour un affichage rapide et optimisé
            $platsFormates = $menuItems->map(function ($item) {
                return [
                    'id_menuitem' => $item->id_menuitem,
                    'nom' => $item->nom,
                    'description' => $item->description,
                    'prix' => $item->prix,
                    'photo_url' => $item->photo_url,
                    'temps_preparation' => $item->temps_preparation,
                    'plat_du_jour' => $item->plat_du_jour,
                    'statut_disponibilite' => $item->statut_disponibilite,
                    'categorie' => $item->categorie ? [
                        'id_categorie' => $item->categorie->id_categorie,
                        'nom' => $item->categorie->nom,
                    ] : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $platsFormates,
                'meta' => [
                    'total' => $platsFormates->count(),
                    'categorie_filtree' => $categorieId,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des plats disponibles (employé)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plats disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

     * Consulter la liste des plats disponibles (pour les employés)
     * 
     * GET /api/menu/available
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * Retourne uniquement les plats disponibles (statut_disponibilite = true)
     * Format optimisé pour un affichage rapide
     */
    public function available(Request $request)
    {
        try {
            // Paramètre optionnel pour filtrer par catégorie
            $categorieId = $request->query('categorie');
            
            // Requête de base : uniquement les plats disponibles
            $query = MenuItem::with('categorie')
                ->where('statut_disponibilite', true);
            
            // Filtre par catégorie si fourni
            if ($categorieId) {
                $query->where('id_categorie', $categorieId);
            }
            
            // Trier par catégorie puis par nom
            $menuItems = $query->orderBy('id_categorie')
                ->orderBy('nom')
                ->get();
            
            // Formater les données pour un affichage rapide et optimisé
            $platsFormates = $menuItems->map(function ($item) {
                return [
                    'id_menuitem' => $item->id_menuitem,
                    'nom' => $item->nom,
                    'description' => $item->description,
                    'prix' => $item->prix,
                    'photo_url' => $item->photo_url,
                    'temps_preparation' => $item->temps_preparation,
                    'plat_du_jour' => $item->plat_du_jour,
                    'statut_disponibilite' => $item->statut_disponibilite,
                    'categorie' => $item->categorie ? [
                        'id_categorie' => $item->categorie->id_categorie,
                        'nom' => $item->categorie->nom,
                    ] : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $platsFormates,
                'meta' => [
                    'total' => $platsFormates->count(),
                    'categorie_filtree' => $categorieId,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des plats disponibles (employé)', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plats disponibles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
