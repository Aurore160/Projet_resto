<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\Utilisateur;
use App\Models\Parrainage;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Service pour gérer la logique métier des utilisateurs
 * 
 * Cette classe contient toute la logique métier (parrainage, points, etc.)
 * Elle utilise le UserRepository pour accéder aux données.
 * 
 * Avantages :
 * - Logique métier séparée du controller
 * - Réutilisable partout
 * - Facile à tester
 */
class UserService
{
    protected $userRepository;

    /**
     * Constructeur : injection de dépendance
     * 
     * Laravel va automatiquement donner UserRepository quand on demande UserRepositoryInterface
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Créer un utilisateur avec gestion du parrainage
     * 
     * @param array $userData Les données de l'utilisateur
     * @param string|null $referralCode Le code de parrainage (optionnel)
     * @return array ['utilisateur' => Utilisateur, 'token' => string]
     */
    public function createUserWithReferral(array $userData, ?string $referralCode = null): array
    {
        // Hasher le mot de passe
        $userData['mot_de_passe'] = Hash::make($userData['mot_de_passe']);
        $userData['role'] = 'etudiant';
        
        // Définir les valeurs par défaut pour les champs requis
        $userData['statut_compte'] = $userData['statut_compte'] ?? 'actif';
        $userData['points_balance'] = $userData['points_balance'] ?? 0;
        $userData['date_inscription'] = $userData['date_inscription'] ?? now();

        // Gérer le parrainage si un code est fourni
        $parrain = null;
        if ($referralCode) {
            // Utiliser le repository au lieu d'accéder directement au modèle
            $parrain = $this->userRepository->findByReferralCode($referralCode);
            if ($parrain) {
                $userData['parrain_id'] = $parrain->id_utilisateur;
            }
        }

        // Retirer code_parrainage (généré automatiquement par le trigger PostgreSQL)
        unset($userData['code_parrainage']);

        // Créer l'utilisateur via le repository
        try {
            $utilisateur = $this->userRepository->create($userData);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur', [
                'error' => $e->getMessage(),
                'data' => $userData,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Traiter le parrainage si trouvé
        if ($parrain) {
            $this->processReferral($parrain, $utilisateur, $referralCode);
        }

        // Créer le token d'authentification
        $token = $utilisateur->createToken('auth-token')->plainTextToken;

        return [
            'utilisateur' => $utilisateur,
            'token' => $token,
        ];
    }

    /**
     * Traiter le parrainage : créer le parrainage, attribuer les points, envoyer notification
     * 
     * @param \App\Models\Utilisateur $parrain Le parrain
     * @param \App\Models\Utilisateur $filleul Le filleul (nouvel utilisateur)
     * @param string $code Le code de parrainage utilisé
     * @return void
     */
    protected function processReferral($parrain, $filleul, string $code): void
    {
        try {
            // Récupérer les points de parrainage depuis les paramètres de fidélité
            $pointsParrainage = $this->getReferralPoints();

            // Créer l'enregistrement de parrainage
            Parrainage::create([
                'id_parrain' => $parrain->id_utilisateur,
                'id_filleul' => $filleul->id_utilisateur,
                'code_parrainage_utilise' => $code,
                'date_parrainage' => now(),
                'premiere_commande_faite' => false,
                'points_inscription' => $pointsParrainage,
            ]);

            // Attribuer les points au parrain via le repository
            $this->userRepository->incrementPoints($parrain->id_utilisateur, $pointsParrainage);

            // Envoyer une notification au parrain
            Notification::create([
                'id_utilisateur' => $parrain->id_utilisateur,
                'id_commande' => null,
                'type_notification' => 'system',
                'titre' => 'Nouveau filleul parrainé',
                'message' => "{$filleul->prenom} {$filleul->nom} s'est inscrit avec votre code de parrainage. Vous avez gagné {$pointsParrainage} points !",
                'lu' => false,
                'date_creation' => now(),
            ]);

            Log::info('Parrainage créé avec succès', [
                'parrain_id' => $parrain->id_utilisateur,
                'filleul_id' => $filleul->id_utilisateur,
                'code_parrainage' => $code,
                'points_attribues' => $pointsParrainage,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du parrainage', [
                'parrain_id' => $parrain->id_utilisateur ?? null,
                'filleul_id' => $filleul->id_utilisateur,
                'error' => $e->getMessage(),
            ]);
            // Ne pas faire échouer l'inscription si le parrainage échoue
        }
    }

    /**
     * Récupérer les points de parrainage depuis les paramètres de fidélité
     * 
     * @return int Le nombre de points à attribuer
     */
    protected function getReferralPoints(): int
    {
        $parametresFidelite = DB::table('parametres_fidelite')
            ->where('actif', true)
            ->orderBy('date_debut_application', 'desc')
            ->first();

        return $parametresFidelite ? $parametresFidelite->points_parrainage : 10; // Default 10 si pas trouvé
    }
}
