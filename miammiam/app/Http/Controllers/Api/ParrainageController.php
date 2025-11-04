<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Models\Parrainage;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class ParrainageController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Récupérer le code de parrainage de l'utilisateur connecté
     * 
     * GET /api/parrainage/code
     */
    public function getCode(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Le code de parrainage est généré automatiquement par le trigger PostgreSQL
            // Si l'utilisateur n'a pas encore de code (cas rare), retourner null
            return $this->successResponse([
                'code_parrainage' => $utilisateur->code_parrainage,
                'url_partage' => $utilisateur->code_parrainage 
                    ? url('/register') . '?code=' . $utilisateur->code_parrainage 
                    : null,
            ], 'Code de parrainage récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération du code de parrainage',
                ['user_id' => $request->user()->id_utilisateur ?? null]
            );
        }
    }

    /**
     * Récupérer l'historique de parrainage de l'utilisateur connecté
     * 
     * GET /api/parrainage/historique
     * 
     * Retourne la liste des filleuls avec :
     * - Nom et prénom du filleul
     * - Date d'inscription
     * - Points gagnés à l'inscription
     * - Statut de la première commande
     * - Points gagnés à la première commande (si faite)
     * - Date de la première commande (si faite)
     */
    public function historique(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Récupérer tous les parrainages où cet utilisateur est le parrain
            $parrainages = Parrainage::with(['filleul'])
                ->where('id_parrain', $utilisateur->id_utilisateur)
                ->orderBy('date_parrainage', 'desc')
                ->get();
            
            // Formater les données pour la réponse
            $historique = $parrainages->map(function ($parrainage) {
                $filleul = $parrainage->filleul;
                
                return [
                    'id_parrainage' => $parrainage->id_parrainage,
                    'filleul' => [
                        'id_utilisateur' => $filleul->id_utilisateur,
                        'nom' => $filleul->nom,
                        'prenom' => $filleul->prenom,
                        'email' => $filleul->email,
                    ],
                    'date_inscription' => $parrainage->date_parrainage 
                        ? $parrainage->date_parrainage->format('Y-m-d H:i:s') 
                        : null,
                    'points_inscription' => $parrainage->points_inscription,
                    'premiere_commande_faite' => $parrainage->premiere_commande_faite,
                    'date_premiere_commande' => $parrainage->date_premiere_commande 
                        ? $parrainage->date_premiere_commande->format('Y-m-d H:i:s') 
                        : null,
                    'points_premiere_commande' => $parrainage->points_premiere_commande,
                    'total_points_gagnes' => $parrainage->points_inscription 
                        + ($parrainage->points_premiere_commande ?? 0),
                ];
            });
            
            // Calculer les statistiques
            $totalPointsPremiereCommande = $parrainages->sum(function ($parrainage) {
                return $parrainage->points_premiere_commande ?? 0;
            });
            
            $stats = [
                'total_filleuls' => $parrainages->count(),
                'total_points_inscription' => $parrainages->sum('points_inscription'),
                'total_points_premiere_commande' => $totalPointsPremiereCommande,
                'total_points_gagnes' => $parrainages->sum('points_inscription') + $totalPointsPremiereCommande,
                'premieres_commandes_faites' => $parrainages->where('premiere_commande_faite', true)->count(),
            ];
            
            return $this->successResponse([
                'historique' => $historique,
                'statistiques' => $stats,
            ], 'Historique de parrainage récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'historique de parrainage',
                ['user_id' => $request->user()->id_utilisateur ?? null]
            );
        }
    }
}

{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Récupérer le code de parrainage de l'utilisateur connecté
     * 
     * GET /api/parrainage/code
     */
    public function getCode(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Le code de parrainage est généré automatiquement par le trigger PostgreSQL
            // Si l'utilisateur n'a pas encore de code (cas rare), retourner null
            return $this->successResponse([
                'code_parrainage' => $utilisateur->code_parrainage,
                'url_partage' => $utilisateur->code_parrainage 
                    ? url('/register') . '?code=' . $utilisateur->code_parrainage 
                    : null,
            ], 'Code de parrainage récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération du code de parrainage',
                ['user_id' => $request->user()->id_utilisateur ?? null]
            );
        }
    }

    /**
     * Récupérer l'historique de parrainage de l'utilisateur connecté
     * 
     * GET /api/parrainage/historique
     * 
     * Retourne la liste des filleuls avec :
     * - Nom et prénom du filleul
     * - Date d'inscription
     * - Points gagnés à l'inscription
     * - Statut de la première commande
     * - Points gagnés à la première commande (si faite)
     * - Date de la première commande (si faite)
     */
    public function historique(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Récupérer tous les parrainages où cet utilisateur est le parrain
            $parrainages = Parrainage::with(['filleul'])
                ->where('id_parrain', $utilisateur->id_utilisateur)
                ->orderBy('date_parrainage', 'desc')
                ->get();
            
            // Formater les données pour la réponse
            $historique = $parrainages->map(function ($parrainage) {
                $filleul = $parrainage->filleul;
                
                return [
                    'id_parrainage' => $parrainage->id_parrainage,
                    'filleul' => [
                        'id_utilisateur' => $filleul->id_utilisateur,
                        'nom' => $filleul->nom,
                        'prenom' => $filleul->prenom,
                        'email' => $filleul->email,
                    ],
                    'date_inscription' => $parrainage->date_parrainage 
                        ? $parrainage->date_parrainage->format('Y-m-d H:i:s') 
                        : null,
                    'points_inscription' => $parrainage->points_inscription,
                    'premiere_commande_faite' => $parrainage->premiere_commande_faite,
                    'date_premiere_commande' => $parrainage->date_premiere_commande 
                        ? $parrainage->date_premiere_commande->format('Y-m-d H:i:s') 
                        : null,
                    'points_premiere_commande' => $parrainage->points_premiere_commande,
                    'total_points_gagnes' => $parrainage->points_inscription 
                        + ($parrainage->points_premiere_commande ?? 0),
                ];
            });
            
            // Calculer les statistiques
            $totalPointsPremiereCommande = $parrainages->sum(function ($parrainage) {
                return $parrainage->points_premiere_commande ?? 0;
            });
            
            $stats = [
                'total_filleuls' => $parrainages->count(),
                'total_points_inscription' => $parrainages->sum('points_inscription'),
                'total_points_premiere_commande' => $totalPointsPremiereCommande,
                'total_points_gagnes' => $parrainages->sum('points_inscription') + $totalPointsPremiereCommande,
                'premieres_commandes_faites' => $parrainages->where('premiere_commande_faite', true)->count(),
            ];
            
            return $this->successResponse([
                'historique' => $historique,
                'statistiques' => $stats,
            ], 'Historique de parrainage récupéré avec succès');

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'historique de parrainage',
                ['user_id' => $request->user()->id_utilisateur ?? null]
            );
        }
    }
}
