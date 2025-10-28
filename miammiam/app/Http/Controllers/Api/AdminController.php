<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Liste tous les utilisateurs (admin uniquement)
    public function listUsers()
    {
        try {
            $users = Utilisateur::orderBy('date_inscription', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $users,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Crée un nouvel utilisateur (admin uniquement)
    public function createUser(CreateUserRequest $request)
    {
        try {
            $data = $request->validated();
            $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
            $data['statut_compte'] = 'actif';
            
            $utilisateur = Utilisateur::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'statut_compte' => $utilisateur->statut_compte,
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Modifie le rôle d'un utilisateur (admin uniquement)
    public function updateRole(UpdateRoleRequest $request, $id)
    {
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            
            $oldRole = $utilisateur->role;
            $utilisateur->role = $request->role;
            $utilisateur->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès',
                'data' => [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'email' => $utilisateur->email,
                    'ancien_role' => $oldRole,
                    'nouveau_role' => $utilisateur->role,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rôle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Supprime un utilisateur (admin uniquement)
    public function deleteUser($id)
    {
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            
            // Empêcher la suppression de son propre compte
            if ($utilisateur->id_utilisateur === auth()->user()->id_utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte',
                ], 403);
            }
            
            $userName = $utilisateur->nom . ' ' . $utilisateur->prenom;
            $utilisateur->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès',
                'data' => [
                    'utilisateur_supprime' => $userName,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Liste tous les logs de connexion (admin uniquement)
    public function listConnexionLogs()
    {
        try {
            $logs = ConnexionLog::with('utilisateur:id_utilisateur,nom,prenom,email,role')
                                 ->orderBy('created_at', 'desc')
                                 ->paginate(50);
            
            return response()->json([
                'success' => true,
                'data' => $logs,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Logs de connexion d'un utilisateur spécifique (admin uniquement)
    public function getUserConnexionLogs($id)
    {
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            
            $logs = ConnexionLog::where('utilisateur_id', $id)
                                ->orderBy('created_at', 'desc')
                                ->paginate(50);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'utilisateur' => [
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                    ],
                    'logs' => $logs,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Détecte les connexions suspectes (admin uniquement)
    public function getConnexionsSuspectes()
    {
        try {
            $alertes = [];
            
            // 1. Détection : Tentatives échouées multiples (5+ en 10 minutes)
            $tentativesEchouees = DB::table('connexion_log')
                ->select('email', DB::raw('COUNT(*) as nb_tentatives'), DB::raw('MAX(created_at) as derniere_tentative'))
                ->where('statut', 'echec')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->groupBy('email')
                ->having('nb_tentatives', '>=', 5)
                ->get();
            
            foreach ($tentativesEchouees as $tentative) {
                $alertes[] = [
                    'type' => 'tentatives_echouees_multiples',
                    'severite' => 'haute',
                    'email' => $tentative->email,
                    'details' => "Nombre de tentatives échouées : {$tentative->nb_tentatives} en 10 minutes",
                    'derniere_tentative' => $tentative->derniere_tentative,
                ];
            }
            
            // 2. Détection : Connexions depuis IPs multiples récentes (même utilisateur, 3+ IPs différentes en 1 heure)
            $ipsMultiples = DB::table('connexion_log')
                ->select('utilisateur_id', 'email', DB::raw('COUNT(DISTINCT ip_address) as nb_ips'), DB::raw('MAX(created_at) as derniere_connexion'))
                ->where('statut', 'succes')
                ->where('created_at', '>=', now()->subHour())
                ->whereNotNull('utilisateur_id')
                ->groupBy('utilisateur_id', 'email')
                ->having('nb_ips', '>=', 3)
                ->get();
            
            foreach ($ipsMultiples as $ip) {
                $alertes[] = [
                    'type' => 'ips_multiples',
                    'severite' => 'moyenne',
                    'email' => $ip->email,
                    'details' => "Connexions depuis {$ip->nb_ips} adresses IP différentes en 1 heure",
                    'derniere_connexion' => $ip->derniere_connexion,
                ];
            }
            
            // 3. Détection : Nouvelle IP pour un utilisateur (première connexion depuis cette IP)
            $connexionsRecentes = ConnexionLog::with('utilisateur:id_utilisateur,nom,prenom,email')
                                              ->where('statut', 'succes')
                                              ->where('created_at', '>=', now()->subHours(24))
                                              ->get();
            
            foreach ($connexionsRecentes as $log) {
                if ($log->utilisateur) {
                    $anciennes = ConnexionLog::where('utilisateur_id', $log->utilisateur_id)
                                             ->where('ip_address', $log->ip_address)
                                             ->where('created_at', '<', $log->created_at)
                                             ->count();
                    
                    if ($anciennes === 0) {
                        $alertes[] = [
                            'type' => 'nouvelle_ip',
                            'severite' => 'faible',
                            'email' => $log->email,
                            'details' => "Première connexion depuis l'IP : {$log->ip_address}",
                            'date_connexion' => $log->created_at->toDateTimeString(),
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'nb_alertes' => count($alertes),
                    'alertes' => $alertes,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la détection des connexions suspectes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
