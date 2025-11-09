<?php

namespace App\Repositories\Contracts;

use App\Models\Commande;

/**
 * Interface pour le repository du panier
 * 
 * Cette interface définit les méthodes pour gérer le panier.
 */
interface CartRepositoryInterface
{
    /**
     * Récupérer le panier actif d'un utilisateur (ou le créer s'il n'existe pas)
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return Commande|null Le panier trouvé ou créé, ou null en cas d'erreur
     */
    public function getCart(int $userId): ?Commande;

    /**
     * Vérifier si un utilisateur a un panier actif
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return bool True si l'utilisateur a un panier actif
     */
    public function hasCart(int $userId): bool;

    /**
     * Supprimer le panier d'un utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return bool True si la suppression a réussi
     */
    public function clearCart(int $userId): bool;
}

