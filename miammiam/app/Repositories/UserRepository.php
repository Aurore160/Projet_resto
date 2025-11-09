<?php

namespace App\Repositories;

use App\Models\Utilisateur;
use App\Repositories\Contracts\UserRepositoryInterface;

/**

 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Trouver un utilisateur par son email
     * 
     * @param string $email L'email de l'utilisateur
     * @return Utilisateur|null L'utilisateur trouvé ou null
     */
    public function findByEmail(string $email): ?Utilisateur
    {
        return Utilisateur::where('email', $email)->first();
    }

    /**
     * Trouver un utilisateur par son code de parrainage
     * 
     * @param string $code Le code de parrainage
     * @return Utilisateur|null L'utilisateur trouvé ou null
     */
    public function findByReferralCode(string $code): ?Utilisateur
    {
        return Utilisateur::where('code_parrainage', $code)
            ->where('statut_compte', 'actif')
            ->first();
    }

    /**
     * Créer un nouvel utilisateur
     * 
     * @param array $data Les données de l'utilisateur
     * @return Utilisateur L'utilisateur créé
     */
    public function create(array $data): Utilisateur
    {
        return Utilisateur::create($data);
    }

    /**
     * Mettre à jour un utilisateur
     * 
     * @param int $id L'ID de l'utilisateur
     * @param array $data Les données à mettre à jour
     * @return bool True si la mise à jour a réussi
     */
    public function update(int $id, array $data): bool
    {
        $user = Utilisateur::find($id);
        if ($user) {
            return $user->update($data);
        }
        return false;
    }

    /**
     * Trouver un utilisateur par son ID
     * 
     * @param int $id L'ID de l'utilisateur
     * @return Utilisateur|null L'utilisateur trouvé ou null
     */
    public function findById(int $id): ?Utilisateur
    {
        return Utilisateur::find($id);
    }

    /**
     * Incrémenter les points de fidélité d'un utilisateur
     * 
     * @param int $id L'ID de l'utilisateur
     * @param int $points Le nombre de points à ajouter
     * @return bool True si l'opération a réussi
     */
    public function incrementPoints(int $id, int $points): bool
    {
        $user = Utilisateur::find($id);
        if ($user) {
            $user->increment('points_balance', $points);
            return true;
        }
        return false;
    }

    /**
     * Décrémenter les points de fidélité d'un utilisateur
     * 
     * @param int $id L'ID de l'utilisateur
     * @param int $points Le nombre de points à retirer
     * @return bool True si l'opération a réussi
     */
    public function decrementPoints(int $id, int $points): bool
    {
        $user = Utilisateur::find($id);
        if ($user) {
            $user->decrement('points_balance', $points);
            return true;
        }
        return false;
    }
}
