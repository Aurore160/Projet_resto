<?php

namespace App\Repositories;

use App\Models\Commande;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implémentation concrète du OrderRepositoryInterface
 * 
 * Cette classe contient la logique d'accès aux données des commandes.
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Trouver une commande par son ID
     */
    public function findById(int $id): ?Commande
    {
        return Commande::with(['articles.menuItem', 'utilisateur', 'payments'])
            ->find($id);
    }

    /**
     * Trouver une commande par son numéro de commande
     */
    public function findByNumber(string $numero): ?Commande
    {
        return Commande::where('numero_commande', $numero)
            ->with(['articles.menuItem', 'utilisateur', 'payments'])
            ->first();
    }

    /**
     * Créer une nouvelle commande
     */
    public function create(array $data): Commande
    {
        return Commande::create($data);
    }

    /**
     * Mettre à jour une commande
     */
    public function update(int $id, array $data): bool
    {
        $commande = Commande::find($id);
        if ($commande) {
            return $commande->update($data);
        }
        return false;
    }

    /**
     * Récupérer toutes les commandes d'un utilisateur (hors paniers)
     */
    public function findByUser(int $userId, ?string $status = null): Collection
    {
        $query = Commande::where('id_utilisateur', $userId)
            ->where('statut', '!=', 'panier')
            ->with(['articles.menuItem']);

        if ($status) {
            $query->where('statut', $status);
        }

        return $query->orderBy('date_commande', 'desc')->get();
    }

    /**
     * Récupérer les commandes en attente d'un utilisateur
     */
    public function findPendingByUser(int $userId): Collection
    {
        return Commande::where('id_utilisateur', $userId)
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->with(['articles.menuItem'])
            ->orderBy('date_commande', 'desc')
            ->get();
    }

    /**
     * Compter les commandes livrées ou payées d'un utilisateur
     */
    public function countDeliveredOrPaid(int $userId): int
    {
        try {
            // Compter les commandes livrées
            $livrees = Commande::where('id_utilisateur', $userId)
                ->where('statut', 'livree')
                ->count();

            // Compter les commandes avec paiement réussi
            $payees = Commande::where('id_utilisateur', $userId)
                ->whereHas('payments', function ($query) {
                    $query->where('statut_payment', 'paye');
                })
                ->where('statut', '!=', 'livree') // Ne pas compter deux fois
                ->count();

            return $livrees + $payees;
        } catch (\Exception $e) {
            // En cas d'erreur avec la relation payments, compter seulement les livrées
            return Commande::where('id_utilisateur', $userId)
                ->where('statut', 'livree')
                ->count();
        }
    }

    /**
     * Calculer le montant total dépensé par un utilisateur
     */
    public function getTotalSpentByUser(int $userId): float
    {
        return Commande::where('id_utilisateur', $userId)
            ->whereNotIn('statut', ['panier', 'annulee'])
            ->sum('montant_total') ?? 0.0;
    }

    /**
     * Vérifier si une commande appartient à un utilisateur
     */
    public function belongsToUser(int $orderId, int $userId): bool
    {
        return Commande::where('id_commande', $orderId)
            ->where('id_utilisateur', $userId)
            ->exists();
    }
}




namespace App\Repositories;

use App\Models\Commande;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implémentation concrète du OrderRepositoryInterface
 * 
 * Cette classe contient la logique d'accès aux données des commandes.
 */
