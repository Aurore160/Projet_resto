<?php

namespace App\Repositories;

use App\Models\Commande;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation concrète du CartRepositoryInterface
 * 
 * Cette classe contient la logique d'accès aux données du panier.
 */
class CartRepository implements CartRepositoryInterface
{
    /**
     * Récupérer le panier actif d'un utilisateur (ou le créer s'il n'existe pas)
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return Commande|null Le panier trouvé ou créé, ou null en cas d'erreur
     */
    public function getCart(int $userId): ?Commande
    {
        try {
            // Vérifier d'abord avec DB::table pour éviter les problèmes de mapping
            $panierData = DB::table('commandes')
                           ->where('id_utilisateur', $userId)
                           ->where('statut', 'panier')
                           ->orderBy('date_modification', 'desc')
                           ->orderBy('date_commande', 'desc')
                           ->first();
            
            if (!$panierData) {
                // Créer le panier avec DB::table
                $panierId = DB::table('commandes')->insertGetId([
                    'id_utilisateur' => $userId,
                    'statut' => 'panier',
                    'montant_total' => 0,
                    'type_commande' => 'livraison', // Par défaut : livraison
                    'numero_commande' => 'PAN-' . time(), // Valeur temporaire, le trigger va le remplacer
                    'date_commande' => now(),
                    'date_modification' => now(),
                ], 'id_commande');
                
                // Récupérer le panier créé avec le modèle Eloquent
                $panier = Commande::with(['articles.menuItem'])->find($panierId);
            } else {
                // Charger le panier existant avec le modèle Eloquent
                $panier = Commande::with(['articles.menuItem'])->find($panierData->id_commande);
            }
            
            return $panier;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération/création du panier', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Vérifier si un utilisateur a un panier actif
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return bool True si l'utilisateur a un panier actif
     */
    public function hasCart(int $userId): bool
    {
        try {
            $panierData = DB::table('commandes')
                           ->where('id_utilisateur', $userId)
                           ->where('statut', 'panier')
                           ->exists();
            
            return $panierData;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification du panier', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Supprimer le panier d'un utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return bool True si la suppression a réussi
     */
    public function clearCart(int $userId): bool
    {
        try {
            DB::beginTransaction();
            
            $panierData = DB::table('commandes')
                           ->where('id_utilisateur', $userId)
                           ->where('statut', 'panier')
                           ->first();
            
            if (!$panierData) {
                DB::commit();
                return true; // Pas de panier à supprimer, considéré comme succès
            }

            // Charger le modèle Eloquent pour utiliser les méthodes de relation
            $panier = Commande::find($panierData->id_commande);
            
            if ($panier) {
                // Supprimer tous les articles
                $panier->articles()->delete();
                
                // Supprimer le panier
                $panier->delete();
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la suppression du panier', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

