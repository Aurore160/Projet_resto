<?php

namespace App\Repositories\Contracts;

use App\Models\Commande;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface pour le repository des commandes
 * 
 * Cette interface définit les méthodes pour gérer les commandes.
 */
interface OrderRepositoryInterface
{
    /**
     * Trouver une commande par son ID
     * 
     * @param int $id L'ID de la commande
     * @return Commande|null La commande trouvée ou null
     */
    public function findById(int $id): ?Commande;

    /**
     * Trouver une commande par son numéro de commande
     * 
     * @param string $numero Le numéro de commande
     * @return Commande|null La commande trouvée ou null
     */
    public function findByNumber(string $numero): ?Commande;

    /**
     * Créer une nouvelle commande
     * 
     * @param array $data Les données de la commande
     * @return Commande La commande créée
     */
    public function create(array $data): Commande;

    /**
     * Mettre à jour une commande
     * 
     * @param int $id L'ID de la commande
     * @param array $data Les données à mettre à jour
     * @return bool True si la mise à jour a réussi
     */
    public function update(int $id, array $data): bool;

    /**
     * Récupérer toutes les commandes d'un utilisateur (hors paniers)
     * 
     * @param int $userId L'ID de l'utilisateur
     * @param string|null $status Filtrer par statut (optionnel)
     * @return Collection Les commandes trouvées
     */
    public function findByUser(int $userId, ?string $status = null): Collection;

    /**
     * Récupérer les commandes en attente d'un utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return Collection Les commandes en attente
     */
    public function findPendingByUser(int $userId): Collection;

    /**
     * Compter les commandes livrées ou payées d'un utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return int Le nombre de commandes
     */
    public function countDeliveredOrPaid(int $userId): int;

    /**
     * Calculer le montant total dépensé par un utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur
     * @return float Le montant total
     */
    public function getTotalSpentByUser(int $userId): float;

    /**
     * Vérifier si une commande appartient à un utilisateur
     * 
     * @param int $orderId L'ID de la commande
     * @param int $userId L'ID de l'utilisateur
     * @return bool True si la commande appartient à l'utilisateur
     */
    public function belongsToUser(int $orderId, int $userId): bool;
}

