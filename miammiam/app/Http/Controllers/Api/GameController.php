<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Models\Utilisateur;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    use HandlesApiResponses;

    /**
     * Ajouter des points gagnés au jeu à l'utilisateur connecté
     * 
     * POST /api/game/add-points
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPoints(Request $request)
    {
        try {
            // Valider les données
            $validator = Validator::make($request->all(), [
                'points' => 'required|integer|min:1|max:1000', // Maximum 1000 points par partie
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Données invalides',
                    422,
                    $validator->errors()
                );
            }

            $user = $request->user();
            $pointsToAdd = $request->input('points');

            // Vérifier que l'utilisateur existe
            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            // Ajouter les points à l'utilisateur
            DB::beginTransaction();
            try {
                $user->increment('points_balance', $pointsToAdd);
                
                // Recharger l'utilisateur pour obtenir le nouveau solde
                $user->refresh();

                // Créer une notification pour informer l'utilisateur
                Notification::create([
                    'id_utilisateur' => $user->id_utilisateur,
                    'id_commande' => null,
                    'type_notification' => 'system',
                    'titre' => 'Points gagnés au jeu',
                    'message' => "Félicitations ! Vous avez gagné {$pointsToAdd} points au jeu. Votre nouveau solde est de {$user->points_balance} points (équivalent à " . number_format($user->points_balance * 67, 0, ',', ' ') . " FC).",
                    'lu' => false,
                    'date_creation' => now(),
                ]);

                DB::commit();

                return $this->successResponse([
                    'points_added' => $pointsToAdd,
                    'points_balance' => $user->points_balance,
                    'points_value_fc' => $user->points_balance * 67, // 1 point = 67 FC
                    'message' => "Vous avez gagné {$pointsToAdd} points ! Votre nouveau solde est de {$user->points_balance} points.",
                ], 'Points ajoutés avec succès');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erreur lors de l\'ajout des points au jeu', [
                    'user_id' => $user->id_utilisateur,
                    'points' => $pointsToAdd,
                    'error' => $e->getMessage(),
                ]);
                
                return $this->errorResponse(
                    'Erreur lors de l\'ajout des points',
                    500
                );
            }

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de l\'ajout des points',
                ['request' => $request->all()],
                true
            );
        }
    }
}

