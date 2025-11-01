<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCarteItemRequest;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // Récupérer ou créer le panier actif de l'utilisateur
    private function getOrCreatePanier($utilisateurId)
    {
        // Vérifier d'abord avec DB::table pour éviter les problèmes de mapping
        $panierData = DB::table('commandes')
                       ->where('id_utilisateur', $utilisateurId)
                       ->where('statut', 'panier')
                       ->first();
        
        if (!$panierData) {
            // Créer le panier avec DB::table
            $panierId = DB::table('commandes')->insertGetId([
                'id_utilisateur' => $utilisateurId,
                'statut' => 'panier',
                'montant_total' => 0,
                'type_commande' => 'livraison', // Par défaut : livraison
                'numero_commande' => 'PAN-' . time(), // Valeur temporaire, le trigger va le remplacer
                'date_commande' => now(),
                'date_modification' => now(),
            ], 'id_commande');
            
            // Récupérer le panier créé avec le modèle Eloquent
            $panier = Commande::find($panierId);
        } else {
            // Charger le panier existant avec le modèle Eloquent
            $panier = Commande::find($panierData->id_commande);
        }
        
        return $panier;
    }

    // Vérifier que l'article appartient au panier de l'utilisateur
    private function validatePanierArticleOwnership($panierArticle, $utilisateur)
    {
        if ($panierArticle->commande->id_utilisateur !== $utilisateur->id_utilisateur) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        if ($panierArticle->commande->statut !== 'panier') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une commande déjà validée',
            ], 400);
        }

        return null;
    }

    // Mettre à jour le montant total du panier
    private function updatePanierTotal($panier)
    {
        $panier->montant_total = $panier->getTotal();
        $panier->save();
    }

    // Formater la réponse JSON du panier
    private function formatPanierResponse($panier, $message = null)
    {
        $panier->load(['articles.menuItem.categorie']);
        
        $response = [
            'success' => true,
            'data' => [
                'panier' => [
                    'id_panier' => $panier->id_commande,
                    'items' => $panier->articles,
                    'total' => $panier->montant_total,
                    'nb_items' => $panier->getTotalArticles(),
                ],
            ],
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return $response;
    }

    // Voir le contenu du panier (GET /cart)
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Utiliser une requête explicite avec DB pour éviter les problèmes de convention
            $panier = DB::table('commandes')
                       ->where('id_utilisateur', $utilisateur->id_utilisateur)
                       ->where('statut', 'panier')
                       ->first();
            
            if (!$panier) {
                return response()->json([
                    'success' => true,
                    'message' => 'Panier vide',
                    'data' => [
                        'items' => [],
                        'total' => 0,
                        'nb_items' => 0,
                    ],
                ], 200);
            }
            
            // Charger les articles avec DB pour éviter les problèmes de mapping
            $articles = DB::table('commande_articles')
                         ->join('menu_item', 'commande_articles.id_menuitem', '=', 'menu_item.id_menuitem')
                         ->join('categories', 'menu_item.id_categorie', '=', 'categories.id_categorie')
                         ->where('commande_articles.id_commande', $panier->id_commande)
                         ->select(
                             'commande_articles.id_commande_article',
                             'commande_articles.id_commande',
                             'commande_articles.id_menuitem',
                             'commande_articles.quantite',
                             'commande_articles.prix_unitaire',
                             'commande_articles.sous_total',
                             'commande_articles.instructions',
                             'commande_articles.date_ajout',
                             'menu_item.nom as menu_item_nom',
                             'menu_item.description as menu_item_description',
                             'menu_item.photo_url as menu_item_photo_url',
                             'categories.nom as categorie_nom'
                         )
                         ->get();
            
            // Calculer le total et le nombre d'articles
            $total = $articles->sum(function ($article) {
                return $article->prix_unitaire * $article->quantite;
            });
            $nbItems = $articles->sum('quantite');
            
            // Formater les articles pour la réponse
            $items = $articles->map(function ($article) {
                return [
                    'id_commande_article' => $article->id_commande_article,
                    'id_menuitem' => $article->id_menuitem,
                    'quantite' => $article->quantite,
                    'prix_unitaire' => (float) $article->prix_unitaire,
                    'sous_total' => (float) ($article->prix_unitaire * $article->quantite),
                    'menu_item' => [
                        'id_menuitem' => $article->id_menuitem,
                        'nom' => $article->menu_item_nom,
                        'description' => $article->menu_item_description,
                        'photo_url' => $article->menu_item_photo_url,
                        'categorie' => [
                            'nom' => $article->categorie_nom,
                        ],
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id_panier' => $panier->id_commande,
                    'items' => $items,
                    'total' => $total,
                    'nb_items' => $nbItems,
                    'date_creation' => $panier->date_commande,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du panier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Ajouter un plat au panier (POST /cart/add)
    public function add(AddToCartRequest $request)
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

            DB::beginTransaction();

            // Récupérer ou créer le panier actif
            $panier = $this->getOrCreatePanier($utilisateur->id_utilisateur);

            // Vérifier si le plat est déjà dans le panier
            $panierArticle = CommandeArticle::where('id_commande', $panier->id_commande)
                                            ->where('id_menuitem', $data['menu_item_id'])
                                            ->first();

            if ($panierArticle) {
                // Augmenter la quantité
                $panierArticle->quantite += $data['quantite'];
                $panierArticle->save();
            } else {
                // Ajouter un nouvel article
                $panierArticle = CommandeArticle::create([
                    'id_commande' => $panier->id_commande,
                    'id_menuitem' => $data['menu_item_id'],
                    'quantite' => $data['quantite'],
                    'prix_unitaire' => $menuItem->prix,
                ]);
            }

            // Mettre à jour le montant total du panier
            $this->updatePanierTotal($panier);

            DB::commit();

            return response()->json(
                $this->formatPanierResponse($panier, 'Plat ajouté au panier avec succès'),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout au panier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Modifier la quantité d'un article (PUT /cart/items/{id})
    public function updateItem(UpdateCarteItemRequest $request, $id)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            $panierArticle = CommandeArticle::with('commande')
                                            ->where('id_commande_article', $id)
                                            ->first();

            if (!$panierArticle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier',
                ], 404);
            }

            // Vérifier que cet article appartient bien au panier de l'utilisateur
            $validationError = $this->validatePanierArticleOwnership($panierArticle, $utilisateur);
            if ($validationError) {
                return $validationError;
            }

            DB::beginTransaction();

            $panierArticle->quantite = $data['quantite'];
            $panierArticle->save();

            // Mettre à jour le montant total du panier
            $panier = $panierArticle->commande;
            $this->updatePanierTotal($panier);

            DB::commit();

            return response()->json(
                $this->formatPanierResponse($panier, 'Quantité mise à jour avec succès'),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la quantité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Supprimer un article du panier (DELETE /cart/items/{id})
    public function removeItem(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();

            $panierArticle = CommandeArticle::with('commande')
                                            ->where('id_commande_article', $id)
                                            ->first();

            if (!$panierArticle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier',
                ], 404);
            }

            // Vérifier que cet article appartient bien au panier de l'utilisateur
            $validationError = $this->validatePanierArticleOwnership($panierArticle, $utilisateur);
            if ($validationError) {
                return $validationError;
            }

            DB::beginTransaction();

            $panierId = $panierArticle->id_commande;
            $panierArticle->delete();

            // Mettre à jour le montant total du panier
            $panier = Commande::find($panierId);
            $this->updatePanierTotal($panier);

            DB::commit();

            return response()->json(
                $this->formatPanierResponse($panier, 'Article supprimé du panier'),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'article',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Vider complètement le panier (DELETE /cart)
    public function clear(Request $request)
    {
        try {
            $utilisateur = $request->user();

            // Utiliser DB::table pour éviter les problèmes de mapping
            $panierData = DB::table('commandes')
                           ->where('id_utilisateur', $utilisateur->id_utilisateur)
                           ->where('statut', 'panier')
                           ->first();

            if (!$panierData) {
                return response()->json([
                    'success' => true,
                    'message' => 'Le panier est déjà vide',
                ], 200);
            }

            DB::beginTransaction();

            // Charger le modèle Eloquent pour utiliser les méthodes de relation
            $panier = Commande::find($panierData->id_commande);

            // Supprimer tous les articles
            $panier->articles()->delete();

            // Supprimer le panier
            $panier->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Panier vidé avec succès',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du panier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}