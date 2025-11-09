<?php

namespace App\Repositories\Contracts;

use App\Models\Utilisateur;

/**
 * Interface pour le Repository des Utilisateurs
 * 
 * Cette interface définit les méthodes que doit implémenter UserRepository.
 * Cela permet de :
 * - Changer l'implémentation facilement
 * - Créer des mocks pour les tests
 * - Respecter le principe d'inversion de dépendances
 */
interface UserRepositoryInterface
{
    /**
     * Trouver un utilisateur par son email
     * 
     * @param string $email
     * @return Utilisateur|null
     */
    public function findByEmail(string $email): ?Utilisateur;

    /**
     * Trouver un utilisateur par son code de parrainage
     * 
     * @param string $code Code de parrainage
     * @return Utilisateur|null Retourne l'utilisateur si trouvé et actif, null sinon
     */
    public function findByReferralCode(string $code): ?Utilisateur;

    /**
     * Créer un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur
     * @return Utilisateur L'utilisateur créé
     */
    public function create(array $data): Utilisateur;

    /**
     * Mettre à jour un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool True si mis à jour, false sinon
     */
    public function update(int $id, array $data): bool;

    /**
     * Trouver un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return Utilisateur|null
     */
    public function findById(int $id): ?Utilisateur;

    /**
     * Incrémenter les points de fidélité d'un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param int $points Nombre de points à ajouter
     * @return bool True si réussi, false sinon
     */
    public function incrementPoints(int $id, int $points): bool;

    /**
     * Décrémenter les points de fidélité d'un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param int $points Nombre de points à retirer
     * @return bool True si réussi, false sinon
     */
    public function decrementPoints(int $id, int $points): bool;
}

