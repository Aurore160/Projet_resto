<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginEmployeeRequest;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Connexion pour les employés (employe, gerant, admin)
     * 
     * POST /api/login-employe
     */
    public function login(LoginEmployeeRequest $request)
    {
        try {
            $credentials = $request->validated();
            
            $utilisateur = Utilisateur::where('email', $credentials['email'])->first();

            if (!$utilisateur) {
                ConnexionLog::create([
                    'utilisateur_id' => null,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                ], 401);
            }

            // Vérifier que l'utilisateur a un rôle autorisé (employe, gerant ou admin)
            $rolesAutorises = ['employe', 'gerant', 'admin'];
            if (!in_array($utilisateur->role, $rolesAutorises)) {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé : cette connexion est réservée aux employés',
                ], 403);
            }

            $passwordValid = false;
            
            // Vérifie si le mot de passe est hashé (bcrypt commence par $2y$)
            $isPasswordHashed = substr($utilisateur->mot_de_passe, 0, 4) === '$2y$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2a$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2b$';
            
            if ($isPasswordHashed) {
                // Mot de passe hashé : utilise Hash::check()
                $passwordValid = Hash::check($credentials['mot_de_passe'], $utilisateur->mot_de_passe);
            } else {
                // Mot de passe non hashé : comparaison directe
                $passwordValid = $credentials['mot_de_passe'] === $utilisateur->mot_de_passe;
                
                // Si la connexion réussit avec un mot de passe en clair, on le hashe et on met à jour
                if ($passwordValid) {
                    $utilisateur->mot_de_passe = Hash::make($credentials['mot_de_passe']);
                    $utilisateur->save();
                }
            }

            if (!$passwordValid) {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                ], 401);
            }

            if ($utilisateur->statut_compte !== 'actif') {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est ' . $utilisateur->statut_compte,
                ], 403);
            }

            // Connexion réussie
            ConnexionLog::create([
                'utilisateur_id' => $utilisateur->id_utilisateur,
                'email' => $credentials['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'statut' => 'succes',
            ]);

            $token = $utilisateur->createToken('employee-auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'utilisateur' => [
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                        'role' => $utilisateur->role,
                        'statut_compte' => $utilisateur->statut_compte,
                    ],
                    'token' => $token,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginEmployeeRequest;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Connexion pour les employés (employe, gerant, admin)
     * 
     * POST /api/login-employe
     */
    public function login(LoginEmployeeRequest $request)
    {
        try {
            $credentials = $request->validated();
            
            $utilisateur = Utilisateur::where('email', $credentials['email'])->first();

            if (!$utilisateur) {
                ConnexionLog::create([
                    'utilisateur_id' => null,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                ], 401);
            }

            // Vérifier que l'utilisateur a un rôle autorisé (employe, gerant ou admin)
            $rolesAutorises = ['employe', 'gerant', 'admin'];
            if (!in_array($utilisateur->role, $rolesAutorises)) {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé : cette connexion est réservée aux employés',
                ], 403);
            }

            $passwordValid = false;
            
            // Vérifie si le mot de passe est hashé (bcrypt commence par $2y$)
            $isPasswordHashed = substr($utilisateur->mot_de_passe, 0, 4) === '$2y$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2a$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2b$';
            
            if ($isPasswordHashed) {
                // Mot de passe hashé : utilise Hash::check()
                $passwordValid = Hash::check($credentials['mot_de_passe'], $utilisateur->mot_de_passe);
            } else {
                // Mot de passe non hashé : comparaison directe
                $passwordValid = $credentials['mot_de_passe'] === $utilisateur->mot_de_passe;
                
                // Si la connexion réussit avec un mot de passe en clair, on le hashe et on met à jour
                if ($passwordValid) {
                    $utilisateur->mot_de_passe = Hash::make($credentials['mot_de_passe']);
                    $utilisateur->save();
                }
            }

            if (!$passwordValid) {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                ], 401);
            }

            if ($utilisateur->statut_compte !== 'actif') {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'email' => $credentials['email'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'statut' => 'echec',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte est ' . $utilisateur->statut_compte,
                ], 403);
            }

            // Connexion réussie
            ConnexionLog::create([
                'utilisateur_id' => $utilisateur->id_utilisateur,
                'email' => $credentials['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'statut' => 'succes',
            ]);

            $token = $utilisateur->createToken('employee-auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'utilisateur' => [
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                        'role' => $utilisateur->role,
                        'statut_compte' => $utilisateur->statut_compte,
                    ],
                    'token' => $token,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
