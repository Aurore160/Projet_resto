<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Mail\ResetPasswordMail;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();
            $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
            $data['role'] = 'etudiant';

            $utilisateur = Utilisateur::create($data);
            $token = $utilisateur->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'utilisateur' => [
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                        'role' => $utilisateur->role,
                        'statut_compte' => $utilisateur->statut_compte,
                        'points_balance' => $utilisateur->points_balance,
                    ],
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            \Log::info('Tentative de connexion', [
                'all_data' => $request->all(),
                'email' => $request->input('email'),
                'mot_de_passe' => $request->input('mot_de_passe') ? 'présent' : 'absent',
                'headers' => $request->headers->all()
            ]);
            
            $credentials = $request->validated();
            
            \Log::info('Données validées', [
                'email' => $credentials['email'] ?? 'non défini',
                'mot_de_passe_present' => isset($credentials['mot_de_passe']),
            ]);
            
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

            $passwordValid = false;
            
            // Vérifie si le mot de passe est hashé (bcrypt commence par $2y$)
            $isPasswordHashed = substr($utilisateur->mot_de_passe, 0, 4) === '$2y$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2a$' 
                || substr($utilisateur->mot_de_passe, 0, 4) === '$2b$';
            
            \Log::info('Vérification du mot de passe', [
                'email' => $utilisateur->email,
                'isPasswordHashed' => $isPasswordHashed,
                'passwordStart' => substr($utilisateur->mot_de_passe, 0, 10),
                'passwordLength' => strlen($utilisateur->mot_de_passe),
                'providedPasswordLength' => strlen($credentials['mot_de_passe']),
            ]);
            
            if ($isPasswordHashed) {
                // Mot de passe hashé : utilise Hash::check()
                $passwordValid = Hash::check($credentials['mot_de_passe'], $utilisateur->mot_de_passe);
                \Log::info('Résultat Hash::check', ['passwordValid' => $passwordValid]);
            } else {
                // Mot de passe non hashé : comparaison directe
                $passwordValid = $credentials['mot_de_passe'] === $utilisateur->mot_de_passe;
                \Log::info('Résultat comparaison directe', [
                    'passwordValid' => $passwordValid,
                    'providedPassword' => $credentials['mot_de_passe'],
                    'storedPassword' => $utilisateur->mot_de_passe,
                ]);
                
                // Si la connexion réussit avec un mot de passe en clair, on le hashe et on met à jour
                if ($passwordValid) {
                    $utilisateur->mot_de_passe = Hash::make($credentials['mot_de_passe']);
                    $utilisateur->save();
                    \Log::info('Mot de passe hashé et mis à jour');
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

            $token = $utilisateur->createToken('auth-token')->plainTextToken;

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
                        'points_balance' => $utilisateur->points_balance,
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

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $email = $request->validated()['email'];
            
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            $token = Str::random(60);

            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            Mail::to($email)->send(new ResetPasswordMail($token, $email));
            
            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande de réinitialisation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $data = $request->validated();

            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $data['email'])
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré',
                ], 400);
            }

            if (!Hash::check($data['token'], $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide',
                ], 400);
            }

            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expiré (valable 60 minutes)',
                ], 400);
            }

            $utilisateur = Utilisateur::where('email', $data['email'])->first();
            $utilisateur->mot_de_passe = Hash::make($data['mot_de_passe']);
            $utilisateur->save();

            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ], 200);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $utilisateur = $request->user();
            $data = $request->validated();

            // Si l'utilisateur veut changer son mot de passe
            if (isset($data['nouveau_mot_de_passe'])) {
                // Vérifier que le mot de passe actuel est correct
                if (!Hash::check($data['mot_de_passe_actuel'], $utilisateur->mot_de_passe)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le mot de passe actuel est incorrect',
                    ], 400);
                }

                // Mettre à jour avec le nouveau mot de passe
                $data['mot_de_passe'] = Hash::make($data['nouveau_mot_de_passe']);
            }

            // Supprimer les champs temporaires
            unset($data['mot_de_passe_actuel'], $data['nouveau_mot_de_passe'], $data['nouveau_mot_de_passe_confirmation']);

            // Mettre à jour l'utilisateur
            $utilisateur->update($data);
            $utilisateur->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $utilisateur,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Déconnexion sécurisée (pour tous les utilisateurs)
     * 
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if ($utilisateur) {
                // Supprimer le token actuel (celui utilisé pour cette requête)
                $request->user()->currentAccessToken()->delete();
                
                // Log la déconnexion
                \Log::info('Déconnexion utilisateur', [
                    'user_id' => $utilisateur->id_utilisateur,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'ip_address' => $request->ip(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
