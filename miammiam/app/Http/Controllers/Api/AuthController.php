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
use App\Models\Parrainage;
use App\Models\Notification;
use App\Models\ConnexionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();
            $data['mot_de_passe'] = Hash::make($data['mot_de_passe']);
            $data['role'] = 'etudiant';

            // Récupérer le code de parrainage si fourni (avant de créer l'utilisateur)
            $codeParrainage = $request->input('code_parrainage');
            $parrain = null;
            
            // Vérifier le code de parrainage si fourni
            if ($codeParrainage) {
                $parrain = Utilisateur::where('code_parrainage', $codeParrainage)
                    ->where('statut_compte', 'actif')
                    ->first();
                
                // Si le code est valide, lier le filleul au parrain
                if ($parrain) {
                    $data['parrain_id'] = $parrain->id_utilisateur;
                }
                // Si le code est invalide, on continue l'inscription sans parrainage (comme demandé)
            }
            
            // Retirer code_parrainage des données car il est généré automatiquement par le trigger PostgreSQL
            unset($data['code_parrainage']);

            // Créer l'utilisateur
            $utilisateur = Utilisateur::create($data);
            
            // Si un parrain a été trouvé, créer le parrainage et attribuer les points
            if ($parrain) {
                try {
                    // Récupérer les points de parrainage depuis parametres_fidelite
                    $parametresFidelite = DB::table('parametres_fidelite')
                        ->where('actif', true)
                        ->orderBy('date_debut_application', 'desc')
                        ->first();
                    
                    $pointsParrainage = $parametresFidelite ? $parametresFidelite->points_parrainage : 10; // Default 10 si pas trouvé
                    
                    // Créer l'enregistrement dans parrainages
                    $parrainage = Parrainage::create([
                        'id_parrain' => $parrain->id_utilisateur,
                        'id_filleul' => $utilisateur->id_utilisateur,
                        'code_parrainage_utilise' => $codeParrainage,
                        'date_parrainage' => now(),
                        'premiere_commande_faite' => false,
                        'points_inscription' => $pointsParrainage,
                    ]);
                    
                    // Attribuer les points au parrain
                    $parrain->increment('points_balance', $pointsParrainage);
                    
                    // Envoyer une notification au parrain
                    Notification::create([
                        'id_utilisateur' => $parrain->id_utilisateur,
                        'id_commande' => null,
                        'type_notification' => 'system',
                        'titre' => 'Nouveau filleul parrainé',
                        'message' => "{$utilisateur->prenom} {$utilisateur->nom} s'est inscrit avec votre code de parrainage. Vous avez gagné {$pointsParrainage} points !",
                        'lu' => false,
                        'date_creation' => now(),
                    ]);
                    
                    \Log::info('Parrainage créé avec succès', [
                        'parrain_id' => $parrain->id_utilisateur,
                        'filleul_id' => $utilisateur->id_utilisateur,
                        'code_parrainage' => $codeParrainage,
                        'points_attribues' => $pointsParrainage,
                    ]);
                } catch (\Exception $e) {
                    // Log l'erreur mais ne fait pas échouer l'inscription
                    \Log::error('Erreur lors de la création du parrainage', [
                        'parrain_id' => $parrain->id_utilisateur ?? null,
                        'filleul_id' => $utilisateur->id_utilisateur,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
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
                        'photo' => $utilisateur->photo,
                        'role' => $utilisateur->role,
                        'statut_compte' => $utilisateur->statut_compte,
                        'points_balance' => $utilisateur->points_balance,
                        'code_parrainage' => $utilisateur->code_parrainage, // Le code généré par le trigger
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
                        'photo' => $utilisateur->photo,
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

            // Créer un token d'authentification pour connecter automatiquement l'utilisateur
            $token = $utilisateur->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe réinitialisé avec succès',
                'data' => [
                    'token' => $token,
                    'utilisateur' => [
                        'id_utilisateur' => $utilisateur->id_utilisateur,
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                        'photo' => $utilisateur->photo,
                        'role' => $utilisateur->role,
                        'statut_compte' => $utilisateur->statut_compte,
                        'points_balance' => $utilisateur->points_balance,
                    ],
                ],
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
            \Log::info('updateProfile appelé', [
                'user_id' => $request->user()->id_utilisateur,
                'method' => $request->method(),
                'has_photo' => $request->hasFile('photo'),
                'has_photo_input' => $request->input('photo') !== null,
                'all_inputs' => $request->all(),
                'all_files' => $request->allFiles(),
            ]);
            
            $utilisateur = $request->user();
            $data = $request->validated();
            
            \Log::info('Données validées', [
                'data' => $data,
                'data_keys' => array_keys($data),
            ]);

            // Gérer l'upload de photo si fournie
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                
                // Valider le type de fichier
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($photo->getMimeType(), $allowedMimeTypes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier doit être une image (JPEG, PNG, GIF ou WebP)',
                    ], 400);
                }

                // Valider la taille (max 5MB)
                if ($photo->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'image ne doit pas dépasser 5MB',
                    ], 400);
                }

                // Supprimer l'ancienne photo si elle existe
                if ($utilisateur->photo && Storage::disk('public')->exists('profiles/' . basename($utilisateur->photo))) {
                    Storage::disk('public')->delete('profiles/' . basename($utilisateur->photo));
                }

                // Générer un nom unique pour le fichier
                $filename = 'profile_' . $utilisateur->id_utilisateur . '_' . time() . '.' . $photo->getClientOriginalExtension();
                
                // Stocker le fichier dans storage/app/public/profiles
                $path = $photo->storeAs('profiles', $filename, 'public');
                
                // Générer l'URL complète de la photo
                $photoUrl = Storage::disk('public')->url($path);
                
                // S'assurer que l'URL a le port 8000 pour localhost
                // Si l'URL contient localhost sans :8000, l'ajouter
                if (strpos($photoUrl, 'http://localhost/') === 0 || strpos($photoUrl, 'http://localhost/storage/') === 0) {
                    // Remplacer http://localhost/ par http://localhost:8000/
                    $photoUrl = str_replace('http://localhost/', 'http://localhost:8000/', $photoUrl);
                } elseif (strpos($photoUrl, 'http://127.0.0.1/') === 0) {
                    $photoUrl = str_replace('http://127.0.0.1/', 'http://127.0.0.1:8000/', $photoUrl);
                }
                
                \Log::info('Photo uploadée', [
                    'path' => $path,
                    'url_originale' => Storage::disk('public')->url($path),
                    'url_finale' => $photoUrl,
                    'user_id' => $utilisateur->id_utilisateur,
                ]);
                
                // Enregistrer l'URL de la photo dans la base de données
                $data['photo'] = $photoUrl;
            }

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
            
            // Ne pas mettre à jour les champs vides (chaînes vides)
            // Garder seulement les champs qui ont une valeur réelle
            foreach ($data as $key => $value) {
                if ($value === '' || $value === null) {
                    unset($data[$key]);
                }
            }

            // Mettre à jour l'utilisateur
            \Log::info('Avant mise à jour', [
                'ancien_nom' => $utilisateur->nom,
                'ancien_prenom' => $utilisateur->prenom,
                'ancien_email' => $utilisateur->email,
                'ancienne_photo' => $utilisateur->photo,
            ]);
            
            $utilisateur->update($data);
            $utilisateur->refresh();
            
            \Log::info('Après mise à jour', [
                'nouveau_nom' => $utilisateur->nom,
                'nouveau_prenom' => $utilisateur->prenom,
                'nouveau_email' => $utilisateur->email,
                'nouvelle_photo' => $utilisateur->photo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $utilisateur,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du profil', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
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

            $utilisateur = $request->user();
            $data = $request->validated();
            
            \Log::info('Données validées', [
                'data' => $data,
                'data_keys' => array_keys($data),
            ]);

            // Gérer l'upload de photo si fournie
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                
                // Valider le type de fichier
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($photo->getMimeType(), $allowedMimeTypes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier doit être une image (JPEG, PNG, GIF ou WebP)',
                    ], 400);
                }

                // Valider la taille (max 5MB)
                if ($photo->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'image ne doit pas dépasser 5MB',
                    ], 400);
                }

                // Supprimer l'ancienne photo si elle existe
                if ($utilisateur->photo && Storage::disk('public')->exists('profiles/' . basename($utilisateur->photo))) {
                    Storage::disk('public')->delete('profiles/' . basename($utilisateur->photo));
                }

                // Générer un nom unique pour le fichier
                $filename = 'profile_' . $utilisateur->id_utilisateur . '_' . time() . '.' . $photo->getClientOriginalExtension();
                
                // Stocker le fichier dans storage/app/public/profiles
                $path = $photo->storeAs('profiles', $filename, 'public');
                
                // Générer l'URL complète de la photo
                $photoUrl = Storage::disk('public')->url($path);
                
                // S'assurer que l'URL a le port 8000 pour localhost
                // Si l'URL contient localhost sans :8000, l'ajouter
                if (strpos($photoUrl, 'http://localhost/') === 0 || strpos($photoUrl, 'http://localhost/storage/') === 0) {
                    // Remplacer http://localhost/ par http://localhost:8000/
                    $photoUrl = str_replace('http://localhost/', 'http://localhost:8000/', $photoUrl);
                } elseif (strpos($photoUrl, 'http://127.0.0.1/') === 0) {
                    $photoUrl = str_replace('http://127.0.0.1/', 'http://127.0.0.1:8000/', $photoUrl);
                }
                
                \Log::info('Photo uploadée', [
                    'path' => $path,
                    'url_originale' => Storage::disk('public')->url($path),
                    'url_finale' => $photoUrl,
                    'user_id' => $utilisateur->id_utilisateur,
                ]);
                
                // Enregistrer l'URL de la photo dans la base de données
                $data['photo'] = $photoUrl;
            }

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
            
            // Ne pas mettre à jour les champs vides (chaînes vides)
            // Garder seulement les champs qui ont une valeur réelle
            foreach ($data as $key => $value) {
                if ($value === '' || $value === null) {
                    unset($data[$key]);
                }
            }

            // Mettre à jour l'utilisateur
            \Log::info('Avant mise à jour', [
                'ancien_nom' => $utilisateur->nom,
                'ancien_prenom' => $utilisateur->prenom,
                'ancien_email' => $utilisateur->email,
                'ancienne_photo' => $utilisateur->photo,
            ]);
            
            $utilisateur->update($data);
            $utilisateur->refresh();
            
            \Log::info('Après mise à jour', [
                'nouveau_nom' => $utilisateur->nom,
                'nouveau_prenom' => $utilisateur->prenom,
                'nouveau_email' => $utilisateur->email,
                'nouvelle_photo' => $utilisateur->photo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => $utilisateur,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du profil', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
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
