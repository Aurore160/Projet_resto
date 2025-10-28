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
            $credentials = $request->validated();
            
            $utilisateur = Utilisateur::where('email', $credentials['email'])->first();

            // Tentative échouée : email/mot de passe incorrect
            if (!$utilisateur || !Hash::check($credentials['mot_de_passe'], $utilisateur->mot_de_passe)) {
                ConnexionLog::create([
                    'utilisateur_id' => $utilisateur ? $utilisateur->id_utilisateur : null,
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
}

