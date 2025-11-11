<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\UpdatePaymentConfigRequest;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use App\Models\PaymentConfig;
use App\Models\Commande;
use App\Models\MenuItem;
use App\Models\CommandeArticle;
use App\Models\Payment;
use App\Models\Categorie;
use App\Models\Promotion;
use App\Models\PromoMenuItem;
use App\Http\Requests\CreateMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class AdminController extends Controller
{
    // Liste tous les utilisateurs 
    public function listUsers()
    {
        try {
            // Vérifier que la table existe
            if (!Schema::hasTable('utilisateur')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La table utilisateur n\'existe pas',
                ], 500);
            }
            
            // Récupérer les utilisateurs directement depuis la base de données
            $users = DB::table('utilisateur')
                ->orderBy('date_inscription', 'desc')
                ->get();
            
            $result = [];
            
            foreach ($users as $user) {
                // Vérifier que tous les champs nécessaires existent
                $userArray = [
                    'id_utilisateur' => isset($user->id_utilisateur) ? (int)$user->id_utilisateur : 0,
                    'nom' => isset($user->nom) ? (string)$user->nom : '',
                    'prenom' => isset($user->prenom) ? (string)$user->prenom : '',
                    'email' => isset($user->email) ? (string)$user->email : '',
                    'telephone' => isset($user->telephone) && $user->telephone ? (string)$user->telephone : null,
                    'adresse_livraison' => isset($user->adresse_livraison) && $user->adresse_livraison ? (string)$user->adresse_livraison : null,
                    'adresse_facturation' => isset($user->adresse_facturation) && $user->adresse_facturation ? (string)$user->adresse_facturation : null,
                    'role' => isset($user->role) ? (string)$user->role : 'etudiant',
                    'statut_compte' => isset($user->statut_compte) ? (string)$user->statut_compte : 'actif',
                    'points_balance' => isset($user->points_balance) ? (int)$user->points_balance : 0,
                    'code_parrainage' => isset($user->code_parrainage) && $user->code_parrainage ? (string)$user->code_parrainage : null,
                    'parrain_id' => isset($user->parrain_id) && $user->parrain_id ? (int)$user->parrain_id : null,
                    'photo' => isset($user->photo) && $user->photo ? (string)$user->photo : null,
                    'consentement_cookies' => isset($user->consentement_cookies) ? (bool)$user->consentement_cookies : false,
                    'date_inscription' => isset($user->date_inscription) && $user->date_inscription ? (string)$user->date_inscription : null,
                ];
                
                // Récupérer les données employé si l'utilisateur est un employé
                if (Schema::hasTable('employe')) {
                    try {
                        $employe = DB::table('employe')
                            ->where('id_utilisateur', $userArray['id_utilisateur'])
                            ->first();
                        
                        if ($employe) {
                            $userArray['matricule'] = isset($employe->matricule) ? (string)$employe->matricule : null;
                            $userArray['role_specifique'] = isset($employe->role_specifique) ? (string)$employe->role_specifique : null;
                            $userArray['date_embauche'] = isset($employe->date_embauche) && $employe->date_embauche ? (string)$employe->date_embauche : null;
                            $userArray['salaire'] = isset($employe->salaire) && $employe->salaire ? (float)$employe->salaire : null;
                            $userArray['statut_employe'] = isset($employe->statut) && $employe->statut ? (string)$employe->statut : null;
                        } else {
                            $userArray['matricule'] = null;
                            $userArray['role_specifique'] = null;
                            $userArray['date_embauche'] = null;
                            $userArray['salaire'] = null;
                            $userArray['statut_employe'] = null;
                        }
                    } catch (\Exception $e) {
                        // Si erreur lors de la récupération des données employé, ajouter des valeurs null
                        $userArray['matricule'] = null;
                        $userArray['role_specifique'] = null;
                        $userArray['date_embauche'] = null;
                        $userArray['salaire'] = null;
                        $userArray['statut_employe'] = null;
                    }
                } else {
                    $userArray['matricule'] = null;
                    $userArray['role_specifique'] = null;
                    $userArray['date_embauche'] = null;
                    $userArray['salaire'] = null;
                    $userArray['statut_employe'] = null;
                }
                
                $result[] = $userArray;
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des utilisateurs', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    // Récupère un utilisateur par ID 
    public function getUser($id)
    {
        try {
            $user = Utilisateur::findOrFail($id);
            
            $photoValue = $user->getAttributes()['photo'] ?? $user->getOriginal('photo') ?? null;
            
            $userArray = [
                'id_utilisateur' => $user->id_utilisateur,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'adresse_livraison' => $user->adresse_livraison,
                'adresse_facturation' => $user->adresse_facturation,
                'role' => $user->role,
                'statut_compte' => $user->statut_compte,
                'points_balance' => $user->points_balance,
                'code_parrainage' => $user->code_parrainage,
                'parrain_id' => $user->parrain_id,
                'photo' => $photoValue,
                'consentement_cookies' => $user->consentement_cookies,
                'date_inscription' => $user->date_inscription ? ($user->date_inscription instanceof \DateTime ? $user->date_inscription->format('Y-m-d H:i:s') : (string)$user->date_inscription) : null,
            ];
            
            // Récupérer les données employé si l'utilisateur est un employé
            try {
                $employe = DB::table('employe')
                    ->where('id_utilisateur', $user->id_utilisateur)
                    ->first();
                
                if ($employe) {
                    $userArray['matricule'] = $employe->matricule;
                    $userArray['role_specifique'] = $employe->role_specifique;
                    $userArray['date_embauche'] = $employe->date_embauche;
                    $userArray['salaire'] = $employe->salaire ? (float)$employe->salaire : null;
                    $userArray['statut_employe'] = $employe->statut;
                } else {
                    $userArray['matricule'] = null;
                    $userArray['role_specifique'] = null;
                    $userArray['date_embauche'] = null;
                    $userArray['salaire'] = null;
                    $userArray['statut_employe'] = null;
                }
            } catch (\Exception $e) {
                
                $userArray['matricule'] = null;
                $userArray['role_specifique'] = null;
                $userArray['date_embauche'] = null;
                $userArray['salaire'] = null;
                $userArray['statut_employe'] = null;
            }
            
            return response()->json([
                'success' => true,
                'data' => $userArray,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'utilisateur', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    // Crée un nouvel utilisateur 
    public function createUser(CreateUserRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Séparer les données utilisateur des données employe
            $dataUtilisateur = [
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'mot_de_passe' => Hash::make($validated['mot_de_passe']),
                'telephone' => $validated['telephone'] ?? null,
                'adresse_livraison' => $validated['adresse_livraison'] ?? null,
                'adresse_facturation' => $validated['adresse_facturation'] ?? null,
                'role' => $validated['role'],
                'statut_compte' => 'actif',
            ];
            
            // Créer l'utilisateur
            $utilisateur = Utilisateur::create($dataUtilisateur);
            
            $responseData = [
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'nom' => $utilisateur->nom,
                'prenom' => $utilisateur->prenom,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'statut_compte' => $utilisateur->statut_compte,
            ];
            
            // Si le rôle est 'employe' et que les champs employe sont fournis, créer aussi dans employe
            if ($validated['role'] === 'employe' && isset($validated['matricule'])) {
                // Vérifier si la table employe existe
                if (!Schema::hasTable('employe')) {
                    throw new \Exception('La table employe n\'existe pas. Veuillez exécuter la migration.');
                }
                
                $dataEmploye = [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'matricule' => $validated['matricule'],
                    'role_specifique' => $validated['role_specifique'],
                    'date_embauche' => $validated['date_embauche'],
                    'statut' => $validated['statut'] ?? 'actif',
                ];
                
                // Ajouter le salaire s'il est fourni
                if (isset($validated['salaire'])) {
                    $dataEmploye['salaire'] = $validated['salaire'];
                }
                
                // Créer l'enregistrement dans employe
                try {
                    // Spécifier explicitement la clé primaire car la table utilise 'id_employe' et non 'id'
                    $idEmploye = DB::table('employe')->insertGetId($dataEmploye, 'id_employe');
                } catch (\Exception $e) {
                    // Si erreur, supprimer l'utilisateur créé et relancer l'erreur
                    $utilisateur->delete();
                    throw new \Exception('Erreur lors de la création de l\'enregistrement employe: ' . $e->getMessage());
                }
        
                // Récupérer l'enregistrement créé
                $employe = DB::table('employe')->where('id_employe', $idEmploye)->first();
                
                // Ajouter les informations employe à la réponse
                $responseData['employe'] = [
                    'id_employe' => $employe->id_employe,
                    'matricule' => $employe->matricule,
                    'role_specifique' => $employe->role_specifique,
                    'date_embauche' => $employe->date_embauche,
                    'salaire' => $employe->salaire,
                    'statut' => $employe->statut,
                ];
                
                // Logger la création
                \Log::info('Utilisateur et employé créés automatiquement', [
                    'utilisateur_id' => $utilisateur->id_utilisateur,
                    'employe_id' => $idEmploye,
                    'matricule' => $employe->matricule,
                    'role_specifique' => $employe->role_specifique,
                    'admin_id' => $request->user()->id_utilisateur,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $validated['role'] === 'employe' && isset($validated['matricule']) 
                    ? 'Utilisateur et employé créés avec succès' 
                    : 'Utilisateur créé avec succès',
                'data' => $responseData,
            ], 201);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'utilisateur', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
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

    // Met à jour un utilisateur (admin uniquement)
    public function updateUser(Request $request, $id)
    {
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            
            $validated = $request->validate([
                'nom' => 'sometimes|string|max:100',
                'prenom' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|unique:utilisateur,email,' . $id . ',id_utilisateur',
                'mot_de_passe' => 'sometimes|nullable|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
                'telephone' => 'nullable|string|max:20',
                'adresse_livraison' => 'nullable|string',
                'adresse_facturation' => 'nullable|string',
                'role' => 'sometimes|in:etudiant,employe,gerant,admin',
                'statut_compte' => 'sometimes|in:actif,inactif',
                // Champs employé optionnels
                'matricule' => 'sometimes|string|max:20',
                'role_specifique' => 'sometimes|in:cuisinier,serveur,livreur,caissier,manager',
                'date_embauche' => 'sometimes|date',
                'salaire' => 'sometimes|nullable|numeric|min:0',
                'statut' => 'sometimes|in:actif,inactif,congé,licencie',
            ], [
                'email.unique' => 'Cet email est déjà utilisé',
                'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères',
                'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial',
            ]);

            // Mettre à jour les champs fournis
            if (isset($validated['nom']) && !empty($validated['nom'])) {
                $utilisateur->nom = $validated['nom'];
            }
            if (isset($validated['prenom']) && !empty($validated['prenom'])) {
                $utilisateur->prenom = $validated['prenom'];
            }
            if (isset($validated['email']) && !empty($validated['email'])) {
                $utilisateur->email = $validated['email'];
            }
            if (isset($validated['mot_de_passe']) && !empty($validated['mot_de_passe'])) {
                $utilisateur->mot_de_passe = Hash::make($validated['mot_de_passe']);
            }
            if (isset($validated['telephone'])) {
                $utilisateur->telephone = $validated['telephone'] ?: null;
            }
            if (isset($validated['adresse_livraison'])) {
                $utilisateur->adresse_livraison = $validated['adresse_livraison'] ?: null;
            }
            if (isset($validated['adresse_facturation'])) {
                $utilisateur->adresse_facturation = $validated['adresse_facturation'] ?: null;
            }
            if (isset($validated['role'])) {
                $utilisateur->role = $validated['role'];
            }
            if (isset($validated['statut_compte'])) {
                $utilisateur->statut_compte = $validated['statut_compte'];
            }
            
            $utilisateur->save();
            
            // Gérer les données employé si le rôle est 'employe' et que des champs employé sont fournis
            if ($utilisateur->role === 'employe' && (isset($validated['matricule']) || isset($validated['role_specifique']) || isset($validated['date_embauche']))) {
                $employe = DB::table('employe')->where('id_utilisateur', $utilisateur->id_utilisateur)->first();
                
                if ($employe) {
                    // Mettre à jour l'employé existant
                    $updateData = [];
                    if (isset($validated['matricule']) && !empty($validated['matricule'])) {
                        $updateData['matricule'] = $validated['matricule'];
                    }
                    if (isset($validated['role_specifique'])) {
                        $updateData['role_specifique'] = $validated['role_specifique'];
                    }
                    if (isset($validated['date_embauche'])) {
                        $updateData['date_embauche'] = $validated['date_embauche'];
                    }
                    if (isset($validated['salaire'])) {
                        $updateData['salaire'] = $validated['salaire'];
                    }
                    if (isset($validated['statut'])) {
                        $updateData['statut'] = $validated['statut'];
                    }
                    
                    if (!empty($updateData)) {
                        DB::table('employe')->where('id_utilisateur', $utilisateur->id_utilisateur)->update($updateData);
                    }
                } else {
                    // Créer un nouvel enregistrement employé si les champs requis sont fournis
                    if (isset($validated['matricule']) && isset($validated['role_specifique']) && isset($validated['date_embauche'])) {
                        DB::table('employe')->insert([
                            'id_utilisateur' => $utilisateur->id_utilisateur,
                            'matricule' => $validated['matricule'],
                            'role_specifique' => $validated['role_specifique'],
                            'date_embauche' => $validated['date_embauche'],
                            'salaire' => $validated['salaire'] ?? null,
                            'statut' => $validated['statut'] ?? 'actif',
                        ]);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'email' => $utilisateur->email,
                    'role' => $utilisateur->role,
                    'statut_compte' => $utilisateur->statut_compte,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Modifie le statut d'un utilisateur (admin uniquement)
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'statut_compte' => 'required|in:actif,inactif',
            ], [
                'statut_compte.required' => 'Le statut est obligatoire',
                'statut_compte.in' => 'Le statut doit être "actif" ou "inactif"',
            ]);

            $utilisateur = Utilisateur::findOrFail($id);
            
            $oldStatus = $utilisateur->statut_compte;
            $utilisateur->statut_compte = $validated['statut_compte'];
            $utilisateur->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'email' => $utilisateur->email,
                    'ancien_statut' => $oldStatus,
                    'nouveau_statut' => $utilisateur->statut_compte,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
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
            
            // Supprimer d'abord les enregistrements liés dans la table employe si l'utilisateur est un employé
            if ($utilisateur->role === 'employe') {
                try {
                    DB::table('employe')->where('id_utilisateur', $utilisateur->id_utilisateur)->delete();
                } catch (\Exception $e) {
                    // Si la table employe n'existe pas ou s'il n'y a pas d'enregistrement, continuer
                    \Log::warning('Erreur lors de la suppression de l\'employé', [
                        'user_id' => $utilisateur->id_utilisateur,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Supprimer l'utilisateur
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
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Erreur de contrainte de clé étrangère
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            \Log::error('Erreur de contrainte lors de la suppression de l\'utilisateur', [
                'user_id' => $id,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);
            
            // Vérifier si c'est une erreur de contrainte de clé étrangère
            if (strpos($errorMessage, 'foreign key') !== false || strpos($errorMessage, 'violates foreign key') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cet utilisateur car il est lié à d\'autres enregistrements (commandes, parrainages, etc.). Veuillez d\'abord supprimer ou modifier ces enregistrements.',
                    'error' => config('app.debug') ? $errorMessage : null,
                ], 409); // 409 Conflict
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => config('app.debug') ? $errorMessage : 'Une erreur est survenue',
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'utilisateur', [
                'user_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
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
                ->havingRaw('COUNT(*) >= 5')
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
                ->havingRaw('COUNT(DISTINCT ip_address) >= 3')
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

    /**
     * Créer un enregistrement employé dans la table employe (admin uniquement)
     * 
     * POST /api/admin/employees
     * 
     * Permet de créer un enregistrement dans la table employe pour un utilisateur
     * qui a déjà le rôle 'employe' dans la table utilisateur
     */
    public function createEmployee(Request $request)
    {
        try {
            // Validation des données directement dans le contrôleur
            $validated = $request->validate([
                'id_utilisateur' => 'required|integer|exists:utilisateur,id_utilisateur',
                'matricule' => 'required|string|max:20|unique:employe,matricule',
                'role_specifique' => 'required|in:cuisinier,serveur,livreur,caissier,manager',
                'date_embauche' => 'required|date',
                'salaire' => 'nullable|numeric|min:0',
                'statut' => 'nullable|in:actif,inactif,congé,licencie',
            ], [
                'id_utilisateur.required' => 'L\'identifiant de l\'utilisateur est obligatoire',
                'id_utilisateur.exists' => 'L\'utilisateur spécifié n\'existe pas',
                'matricule.required' => 'Le matricule est obligatoire',
                'matricule.unique' => 'Ce matricule est déjà utilisé',
                'role_specifique.required' => 'Le rôle spécifique est obligatoire',
                'role_specifique.in' => 'Le rôle spécifique doit être : cuisinier, serveur, livreur, caissier ou manager',
                'date_embauche.required' => 'La date d\'embauche est obligatoire',
                'date_embauche.date' => 'La date d\'embauche doit être une date valide',
                'salaire.numeric' => 'Le salaire doit être un nombre',
                'salaire.min' => 'Le salaire ne peut pas être négatif',
                'statut.in' => 'Le statut doit être : actif, inactif, congé ou licencie',
            ]);

            // Vérifier que l'utilisateur existe et a le rôle 'employe'
            $utilisateur = Utilisateur::find($validated['id_utilisateur']);
            
            if (!$utilisateur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ], 404);
            }

            if ($utilisateur->role !== 'employe') {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'utilisateur doit avoir le rôle "employe" pour être enregistré comme employé',
                ], 400);
            }

            // Vérifier que l'utilisateur n'a pas déjà un enregistrement dans employe
            $employeExistant = DB::table('employe')
                ->where('id_utilisateur', $validated['id_utilisateur'])
                ->exists();

            if ($employeExistant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur a déjà un enregistrement dans la table employe',
                ], 400);
            }

            // Préparer les données pour l'insertion
            $dataEmploye = [
                'id_utilisateur' => $validated['id_utilisateur'],
                'matricule' => $validated['matricule'],
                'role_specifique' => $validated['role_specifique'],
                'date_embauche' => $validated['date_embauche'],
                'statut' => $validated['statut'] ?? 'actif',
            ];

            // Ajouter le salaire s'il est fourni
            if (isset($validated['salaire'])) {
                $dataEmploye['salaire'] = $validated['salaire'];
            }

            // Créer l'enregistrement dans la table employe
            // Spécifier explicitement la clé primaire car la table utilise 'id_employe' et non 'id'
            $idEmploye = DB::table('employe')->insertGetId($dataEmploye, 'id_employe');

            // Récupérer l'enregistrement créé
            $employe = DB::table('employe')
                ->where('id_employe', $idEmploye)
                ->first();

            // Logger la création
            \Log::info('Employé créé par un administrateur', [
                'employe_id' => $idEmploye,
                'id_utilisateur' => $validated['id_utilisateur'],
                'matricule' => $validated['matricule'],
                'role_specifique' => $validated['role_specifique'],
                'admin_id' => $request->user()->id_utilisateur,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => [
                    'id_employe' => $employe->id_employe,
                    'id_utilisateur' => $employe->id_utilisateur,
                    'matricule' => $employe->matricule,
                    'role_specifique' => $employe->role_specifique,
                    'date_embauche' => $employe->date_embauche,
                    'salaire' => $employe->salaire,
                    'statut' => $employe->statut,
                    'utilisateur' => [
                        'nom' => $utilisateur->nom,
                        'prenom' => $utilisateur->prenom,
                        'email' => $utilisateur->email,
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de l\'employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'employé',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
                'file' => config('app.debug') ? $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * Lister tous les employés (admin uniquement)
     * 
     * GET /api/admin/employees
     */
    public function listEmployees()
    {
        try {
            // Vérifier si la table employe existe
            if (!Schema::hasTable('employe')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'La table employe n\'existe pas encore. Aucun employé enregistré.',
                ], 200);
            }

            $employees = DB::table('employe')
                ->join('utilisateur', 'employe.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->select(
                    'employe.id_employe',
                    'employe.id_utilisateur',
                    'employe.matricule',
                    'employe.role_specifique',
                    'employe.date_embauche',
                    'employe.salaire',
                    'employe.statut',
                    'employe.date_fin_contrat',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone',
                    'utilisateur.role'
                )
                ->orderBy('employe.id_employe', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $employees,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des employés', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des employés',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Récupère un employé par ID (admin uniquement)
     * 
     * GET /api/admin/employees/{id}
     */
    public function getEmployee($id)
    {
        try {
            $employe = DB::table('employe')
                ->join('utilisateur', 'employe.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->select(
                    'employe.id_employe',
                    'employe.id_utilisateur',
                    'employe.matricule',
                    'employe.role_specifique',
                    'employe.date_embauche',
                    'employe.salaire',
                    'employe.statut',
                    'employe.date_fin_contrat',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone',
                    'utilisateur.role',
                    'utilisateur.statut_compte'
                )
                ->where('employe.id_employe', $id)
                ->first();
            
            if (!$employe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé',
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $employe,
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'employé',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Met à jour un employé (admin uniquement)
     * 
     * PUT /api/admin/employees/{id}
     */
    public function updateEmployee(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'matricule' => 'nullable|sometimes|string|max:20|unique:employe,matricule,' . $id . ',id_employe',
                'role_specifique' => 'nullable|sometimes|in:cuisinier,serveur,livreur,caissier,manager',
                'date_embauche' => 'nullable|sometimes|date',
                'salaire' => 'nullable|sometimes|numeric|min:0',
                'statut' => 'nullable|sometimes|in:actif,inactif,congé,licencie',
                'date_fin_contrat' => 'nullable|date',
            ]);
            
            $employe = DB::table('employe')->where('id_employe', $id)->first();
            
            if (!$employe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé',
                ], 404);
            }
            
            $updateData = [];
            // Ne mettre à jour que les champs qui sont présents et non vides
            if (isset($validated['matricule']) && $validated['matricule'] !== null && $validated['matricule'] !== '') {
                $updateData['matricule'] = $validated['matricule'];
            }
            if (isset($validated['role_specifique']) && $validated['role_specifique'] !== null && $validated['role_specifique'] !== '') {
                $updateData['role_specifique'] = $validated['role_specifique'];
            }
            if (isset($validated['date_embauche']) && $validated['date_embauche'] !== null && $validated['date_embauche'] !== '') {
                $updateData['date_embauche'] = $validated['date_embauche'];
            }
            if (isset($validated['salaire']) && $validated['salaire'] !== null && $validated['salaire'] !== '') {
                $updateData['salaire'] = $validated['salaire'];
            }
            if (isset($validated['statut']) && $validated['statut'] !== null && $validated['statut'] !== '') {
                $updateData['statut'] = $validated['statut'];
            }
            // Pour date_fin_contrat, on accepte null pour pouvoir la supprimer
            if (array_key_exists('date_fin_contrat', $validated)) {
                $updateData['date_fin_contrat'] = $validated['date_fin_contrat'];
            }
            
            DB::table('employe')->where('id_employe', $id)->update($updateData);
            
            // Récupérer l'employé mis à jour
            $employeUpdated = DB::table('employe')
                ->join('utilisateur', 'employe.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->select(
                    'employe.id_employe',
                    'employe.id_utilisateur',
                    'employe.matricule',
                    'employe.role_specifique',
                    'employe.date_embauche',
                    'employe.salaire',
                    'employe.statut',
                    'employe.date_fin_contrat',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone'
                )
                ->where('employe.id_employe', $id)
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé mis à jour avec succès',
                'data' => $employeUpdated,
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de l\'employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'employé',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Supprime un employé (admin uniquement)
     * 
     * DELETE /api/admin/employees/{id}
     */
    public function deleteEmployee($id)
    {
        try {
            $employe = DB::table('employe')->where('id_employe', $id)->first();
            
            if (!$employe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employé non trouvé',
                ], 404);
            }
            
            // Supprimer l'enregistrement employé (mais pas l'utilisateur)
            DB::table('employe')->where('id_employe', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Employé supprimé avec succès',
            ], 200);
            
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'foreign key') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cet employé car il est lié à d\'autres enregistrements.',
                ], 409);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'employé',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'employé', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'employé',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Lister toutes les commandes (admin uniquement)
     * 
     * GET /api/admin/orders
     * 
     * Paramètres optionnels :
     * - status : Filtrer par statut
     * - limit : Nombre de résultats à retourner
     * - offset : Décalage pour la pagination
     */
    public function listOrders(Request $request)
    {
        try {
            if (!Schema::hasTable('commandes')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'La table commandes n\'existe pas encore.',
                ], 200);
            }

            $status = $request->query('status');
            $limit = $request->query('limit');
            $offset = (int) ($request->query('offset', 0));

            $query = DB::table('commandes')
                ->join('utilisateur', 'commandes.id_utilisateur', '=', 'utilisateur.id_utilisateur')
                ->where('commandes.statut', '!=', 'panier')
                ->select(
                    'commandes.id_commande',
                    'commandes.numero_commande',
                    'commandes.statut',
                    'commandes.type_commande',
                    'commandes.montant_total',
                    'commandes.frais_livraison',
                    'commandes.points_utilises',
                    'commandes.reduction_points',
                    'commandes.date_commande',
                    'commandes.date_modification',
                    'commandes.adresse_livraison',
                    'utilisateur.id_utilisateur',
                    'utilisateur.nom',
                    'utilisateur.prenom',
                    'utilisateur.email',
                    'utilisateur.telephone'
                );

            if ($status) {
                $query->where('commandes.statut', $status);
            }

            $query->orderBy('commandes.date_commande', 'desc');

            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit));
                $query->skip($offset)->take($limit);
            }

            $commandes = $query->get();

            // Compter le nombre d'articles pour chaque commande
            $commandesAvecArticles = [];
            foreach ($commandes as $commande) {
                $nbArticles = DB::table('commande_articles')
                    ->where('id_commande', $commande->id_commande)
                    ->sum('quantite');
                
                $commandeArray = [
                    'id_commande' => (int) $commande->id_commande,
                    'numero_commande' => $commande->numero_commande,
                    'statut' => $commande->statut,
                    'type_commande' => $commande->type_commande,
                    'montant_total' => $commande->montant_total ? (float) $commande->montant_total : 0,
                    'frais_livraison' => $commande->frais_livraison ? (float) $commande->frais_livraison : 0,
                    'points_utilises' => $commande->points_utilises ? (int) $commande->points_utilises : 0,
                    'reduction_points' => $commande->reduction_points ? (float) $commande->reduction_points : 0,
                    'date_commande' => $commande->date_commande ? (string) $commande->date_commande : null,
                    'date_modification' => $commande->date_modification ? (string) $commande->date_modification : null,
                    'adresse_livraison' => $commande->adresse_livraison ? (string) $commande->adresse_livraison : null,
                    'nb_articles' => (int) $nbArticles,
                    'utilisateur' => [
                        'id_utilisateur' => (int) $commande->id_utilisateur,
                        'nom' => $commande->nom,
                        'prenom' => $commande->prenom,
                        'email' => $commande->email,
                        'telephone' => $commande->telephone,
                    ],
                ];
                
                $commandesAvecArticles[] = $commandeArray;
            }

            return response()->json([
                'success' => true,
                'data' => $commandesAvecArticles,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des commandes (admin uniquement)
     * 
     * GET /api/admin/orders/stats
     */
    public function getOrdersStats()
    {
        try {
            // Vérifier si la table existe
            $tableExists = Schema::hasTable('commandes');
            \Log::info('Vérification table commandes', [
                'table_exists' => $tableExists,
            ]);
            
            if (!$tableExists) {
                \Log::error('La table commandes n\'existe pas');
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total' => 0,
                        'en_attente' => 0,
                        'traitees' => 0,
                        'reclamations' => 0,
                        'tendances' => [
                            'total' => 0,
                            'en_attente' => 0,
                            'traitees' => 0,
                            'reclamations' => 0,
                        ],
                        'par_statut' => [],
                        'evolution_hebdomadaire' => [],
                        'par_type' => [],
                    ],
                ], 200);
            }
            
            // Test simple : compter toutes les commandes
            $testCount = 0;
            try {
                $testCount = DB::table('commandes')->count();
                \Log::info('Test count commandes', ['count' => $testCount]);
            } catch (\Exception $e) {
                \Log::error('Erreur lors du test count', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Statistiques globales
            // Total : TOUTES les commandes (hors panier)
            $total = 0;
            try {
                $total = DB::table('commandes')
                    ->where('statut', '!=', 'panier')
                    ->count();
                \Log::info('Total commandes calculé', [
                    'total' => $total,
                    'test_count' => $testCount,
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur calcul total', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $total = 0;
            }

            // Vérifier d'abord les dates réelles des commandes dans la base
            $datesCommandes = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->select('date_commande', 'statut', 'numero_commande')
                ->orderBy('date_commande', 'desc')
                ->limit(10)
                ->get();
            
            \Log::info('Exemples de dates de commandes réelles', [
                'dates' => $datesCommandes->map(function($c) {
                    return [
                        'date_commande' => $c->date_commande,
                        'numero_commande' => $c->numero_commande,
                        'statut' => $c->statut,
                    ];
                })->toArray(),
            ]);
            
            // Vérifier combien de commandes ont une date_commande NULL
            $commandesSansDate = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereNull('date_commande')
                ->count();
            
            \Log::info('Commandes sans date', [
                'count' => $commandesSansDate,
            ]);

            // Pour les tendances : on compare le mois actuel avec le mois précédent
            $dateDebutMois = now()->startOfMonth();
            $dateFinMois = now()->endOfMonth();
            
            // Log pour vérifier les dates
            \Log::info('Dates de filtrage', [
                'debut_mois' => $dateDebutMois->format('Y-m-d H:i:s'),
                'fin_mois' => $dateFinMois->format('Y-m-d H:i:s'),
                'now' => now()->format('Y-m-d H:i:s'),
            ]);
            
            // Vérifier combien de commandes existent au total
            $totalCommandes = DB::table('commandes')->count();
            $totalCommandesHorsPanier = DB::table('commandes')->where('statut', '!=', 'panier')->count();
            
            // Essayer avec whereBetween (inclut les dates NULL si on ne filtre pas)
            $commandesMoisActuel = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereNotNull('date_commande')
                ->whereBetween('date_commande', [$dateDebutMois, $dateFinMois])
                ->count();
            
            \Log::info('Vérification données commandes', [
                'total_commandes' => $totalCommandes,
                'total_hors_panier' => $totalCommandesHorsPanier,
                'commandes_mois_actuel' => $commandesMoisActuel,
            ]);
            
            // Pour "En attente" : compter uniquement les commandes avec le statut exact "en_attente"
            $enAttente = 0;
            try {
                $enAttente = DB::table('commandes')
                    ->where('statut', 'en_attente')
                    ->count();
                
                // Compter aussi les autres statuts pour information
                $confirmees = DB::table('commandes')->where('statut', 'confirmee')->count();
                $enPreparation = DB::table('commandes')->where('statut', 'en_preparation')->count();
                $pretes = DB::table('commandes')->where('statut', 'pret')->count();
                
                \Log::info('Statuts commandes', [
                    'en_attente' => $enAttente,
                    'confirmee' => $confirmees,
                    'en_preparation' => $enPreparation,
                    'pret' => $pretes,
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur calcul en attente', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $enAttente = 0;
            }

            // Pour "Traitées" : prendre toutes les commandes livrées
            $traitees = 0;
            try {
                $traitees = DB::table('commandes')
                    ->where('statut', 'livree')
                    ->count();
                
                $traiteesAvecDate = DB::table('commandes')
                    ->where('statut', 'livree')
                    ->whereNotNull('date_commande')
                    ->whereBetween('date_commande', [$dateDebutMois, $dateFinMois])
                    ->count();
                
                \Log::info('Traitées', [
                    'avec_filtre_date' => $traiteesAvecDate,
                    'sans_filtre_date' => $traitees,
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur calcul traitées', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $traitees = 0;
            }

            // Statistiques période précédente (mois dernier) - pour calculer les tendances
            $dateDebutMoisPrecedent = now()->subMonth()->startOfMonth();
            $dateFinMoisPrecedent = now()->subMonth()->endOfMonth();
            
            // Total du mois précédent (pour calculer la tendance)
            $totalMoisActuel = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereBetween('date_commande', [$dateDebutMois, $dateFinMois])
                ->count();
            
            $totalPrecedent = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereBetween('date_commande', [$dateDebutMoisPrecedent, $dateFinMoisPrecedent])
                ->count();

            $enAttentePrecedent = DB::table('commandes')
                ->where('statut', 'en_attente')
                ->whereBetween('date_commande', [$dateDebutMoisPrecedent, $dateFinMoisPrecedent])
                ->count();

            $traiteesPrecedent = DB::table('commandes')
                ->where('statut', 'livree')
                ->whereBetween('date_commande', [$dateDebutMoisPrecedent, $dateFinMoisPrecedent])
                ->count();

            // Log pour déboguer
            \Log::info('Statistiques commandes - Calcul tendances', [
                'mois_actuel' => [
                    'debut' => $dateDebutMois->format('Y-m-d H:i:s'),
                    'fin' => $dateFinMois->format('Y-m-d H:i:s'),
                    'total' => $total,
                    'en_attente' => $enAttente,
                    'traitees' => $traitees,
                ],
                'mois_precedent' => [
                    'debut' => $dateDebutMoisPrecedent->format('Y-m-d H:i:s'),
                    'fin' => $dateFinMoisPrecedent->format('Y-m-d H:i:s'),
                    'total' => $totalPrecedent,
                    'en_attente' => $enAttentePrecedent,
                    'traitees' => $traiteesPrecedent,
                ],
            ]);

            // Calculer les tendances en pourcentage
            // Pour le total, on compare le mois actuel avec le mois précédent
            $tendanceTotal = $totalPrecedent > 0 
                ? round((($totalMoisActuel - $totalPrecedent) / $totalPrecedent) * 100, 1)
                : ($totalMoisActuel > 0 ? 100 : 0);
            
            $tendanceEnAttente = $enAttentePrecedent > 0
                ? round((($enAttente - $enAttentePrecedent) / $enAttentePrecedent) * 100, 1)
                : ($enAttente > 0 ? 100 : 0);
            
            $tendanceTraitees = $traiteesPrecedent > 0
                ? round((($traitees - $traiteesPrecedent) / $traiteesPrecedent) * 100, 1)
                : ($traitees > 0 ? 100 : 0);
            
            // Calculer les réclamations depuis la table reclamation (sans 's')
            $reclamations = 0;
            $reclamationsMoisActuel = 0;
            $reclamationsPrecedent = 0;
            $tendanceReclamations = 0;
            
            // Vérifier si la table reclamation existe
            if (Schema::hasTable('reclamation')) {
                try {
                    // Total réclamations (toutes)
                    $reclamations = DB::table('reclamation')->count();
                    
                    // Réclamations du mois actuel (utiliser date_reclamation)
                    $reclamationsMoisActuel = DB::table('reclamation')
                        ->whereNotNull('date_reclamation')
                        ->whereBetween('date_reclamation', [$dateDebutMois, $dateFinMois])
                        ->count();
                    
                    // Réclamations du mois précédent
                    $reclamationsPrecedent = DB::table('reclamation')
                        ->whereNotNull('date_reclamation')
                        ->whereBetween('date_reclamation', [$dateDebutMoisPrecedent, $dateFinMoisPrecedent])
                        ->count();
                    
                    // Calculer la tendance
                    $tendanceReclamations = $reclamationsPrecedent > 0 
                        ? round((($reclamationsMoisActuel - $reclamationsPrecedent) / $reclamationsPrecedent) * 100, 1)
                        : ($reclamationsMoisActuel > 0 ? 100 : 0);
                    
                    \Log::info('Réclamations calculées', [
                        'total' => $reclamations,
                        'mois_actuel' => $reclamationsMoisActuel,
                        'mois_precedent' => $reclamationsPrecedent,
                        'tendance' => $tendanceReclamations,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Erreur calcul réclamations', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $reclamations = 0;
                    $tendanceReclamations = 0;
                }
            } else {
                \Log::info('Table reclamation n\'existe pas');
            }

            \Log::info('Tendances calculées', [
                'tendance_total' => $tendanceTotal,
                'tendance_en_attente' => $tendanceEnAttente,
                'tendance_traitees' => $tendanceTraitees,
            ]);

            // Répartition par statut (mois actuel pour cohérence avec les statistiques)
            $parStatut = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereBetween('date_commande', [$dateDebutMois, $dateFinMois])
                ->select('statut', DB::raw('count(*) as count'))
                ->groupBy('statut')
                ->get()
                ->map(function ($item) {
                    return [
                        'statut' => $item->statut,
                        'count' => (int) $item->count,
                    ];
                });

            // Évolution hebdomadaire (7 derniers jours)
            $evolutionHebdomadaire = [];
            $joursFr = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for ($i = 6; $i >= 0; $i--) {
                $dateObj = now()->subDays($i);
                $date = $dateObj->format('Y-m-d');
                
                // Essayer plusieurs méthodes de filtrage
                $count1 = DB::table('commandes')
                    ->where('statut', '!=', 'panier')
                    ->whereDate('date_commande', $date)
                    ->count();
                
                // Alternative : utiliser whereBetween avec début et fin de jour
                $debutJour = $dateObj->copy()->startOfDay();
                $finJour = $dateObj->copy()->endOfDay();
                $count2 = DB::table('commandes')
                    ->where('statut', '!=', 'panier')
                    ->whereBetween('date_commande', [$debutJour, $finJour])
                    ->count();
                
                // Utiliser le count qui fonctionne
                $count = $count1 > 0 ? $count1 : $count2;
                
                \Log::info("Évolution jour {$date}", [
                    'date' => $date,
                    'count_whereDate' => $count1,
                    'count_whereBetween' => $count2,
                    'count_final' => $count,
                ]);
                
                $evolutionHebdomadaire[] = [
                    'date' => $date,
                    'jour' => $joursFr[$dateObj->dayOfWeek],
                    'count' => (int) $count,
                ];
            }

            // Répartition par type de commande (mois actuel pour cohérence avec les statistiques)
            $parType = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->whereBetween('date_commande', [$dateDebutMois, $dateFinMois])
                ->select('type_commande', DB::raw('count(*) as count'))
                ->groupBy('type_commande')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->type_commande,
                        'count' => (int) $item->count,
                    ];
                });

            $responseData = [
                'success' => true,
                'data' => [
                    'total' => $total,
                    'en_attente' => $enAttente,
                    'traitees' => $traitees,
                    'reclamations' => $reclamations,
                    'tendances' => [
                        'total' => $tendanceTotal,
                        'en_attente' => $tendanceEnAttente,
                        'traitees' => $tendanceTraitees,
                        'reclamations' => $tendanceReclamations,
                    ],
                    'par_statut' => $parStatut,
                    'evolution_hebdomadaire' => $evolutionHebdomadaire,
                    'par_type' => $parType,
                ],
            ];
            
            \Log::info('Réponse finale getOrdersStats', [
                'total' => $total,
                'en_attente' => $enAttente,
                'traitees' => $traitees,
                'response_data' => $responseData,
            ]);
            
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques des commandes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Obtenir la configuration des paiements (pour l'administrateur)
     * 
     * GET /api/admin/payment/config
     * 
     * Retourne la configuration actuelle avec les clés masquées pour sécurité
     */
    public function getPaymentConfig()
    {
        try {
            // Récupérer la configuration active
            $config = PaymentConfig::where('provider', 'easypay')
                ->where('active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            // Si aucune config en base, lire depuis .env (pour compatibilité)
            if (!$config) {
                $configFromEnv = [
                    'provider' => 'easypay',
                    'mode' => env('EASYPAY_MODE', 'sandbox'),
                    'cid' => env('EASYPAY_CID'),
                    'publishable_key' => env('EASYPAY_PUBLISHABLE_KEY'),
                    'active' => true,
                    'source' => 'env', // Indique que la config vient du .env
                ];

                // Masquer les clés pour la sécurité
                $configFromEnv['cid_masked'] = $configFromEnv['cid'] 
                    ? PaymentConfig::maskKey($configFromEnv['cid']) 
                    : null;
                $configFromEnv['publishable_key_masked'] = $configFromEnv['publishable_key'] 
                    ? PaymentConfig::maskKey($configFromEnv['publishable_key']) 
                    : null;

                // Ne pas exposer les clés complètes
                unset($configFromEnv['cid']);
                unset($configFromEnv['publishable_key']);

                return response()->json([
                    'success' => true,
                    'data' => $configFromEnv,
                    'message' => 'Configuration chargée depuis le fichier .env',
                ], 200);
            }

            // Masquer les clés pour l'affichage sécurisé
            $configData = [
                'id_payment_config' => $config->id_payment_config,
                'provider' => $config->provider,
                'mode' => $config->mode,
                'cid_masked' => PaymentConfig::maskKey($config->cid),
                'publishable_key_masked' => PaymentConfig::maskKey($config->publishable_key),
                'active' => $config->active,
                'notes' => $config->notes,
                'created_by' => $config->created_by,
                'updated_by' => $config->updated_by,
                'created_at' => $config->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $config->updated_at->format('Y-m-d H:i:s'),
                'source' => 'database',
            ];

            // Ajouter les infos de l'admin qui a créé/modifié
            if ($config->created_by) {
                $createdBy = Utilisateur::find($config->created_by);
                if ($createdBy) {
                    $configData['created_by_user'] = [
                        'id' => $createdBy->id_utilisateur,
                        'nom' => $createdBy->nom,
                        'prenom' => $createdBy->prenom,
                        'email' => $createdBy->email,
                    ];
                }
            }

            if ($config->updated_by) {
                $updatedBy = Utilisateur::find($config->updated_by);
                if ($updatedBy) {
                    $configData['updated_by_user'] = [
                        'id' => $updatedBy->id_utilisateur,
                        'nom' => $updatedBy->nom,
                        'prenom' => $updatedBy->prenom,
                        'email' => $updatedBy->email,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $configData,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la configuration des paiements', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Mettre à jour la configuration des paiements (pour l'administrateur)
     * 
     * PUT /api/admin/payment/config
     * 
     * Permet de créer ou mettre à jour la configuration des paiements
     */
    public function updatePaymentConfig(UpdatePaymentConfigRequest $request)
    {
        try {
            DB::beginTransaction();

            $adminId = $request->user()->id_utilisateur;
            $data = $request->validated();

            // Désactiver toutes les autres configs du même provider
            PaymentConfig::where('provider', $data['provider'] ?? 'easypay')
                ->where('active', true)
                ->update(['active' => false]);

            // Vérifier s'il existe déjà une config inactive à réutiliser
            $existingConfig = PaymentConfig::where('provider', $data['provider'] ?? 'easypay')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingConfig) {
                // Mettre à jour la config existante
                $existingConfig->mode = $data['mode'];
                $existingConfig->cid = $data['cid']; // Sera chiffré automatiquement via le mutateur
                $existingConfig->publishable_key = $data['publishable_key']; // Sera chiffré automatiquement
                $existingConfig->active = $data['active'] ?? true;
                $existingConfig->updated_by = $adminId;
                $existingConfig->notes = $data['notes'] ?? $existingConfig->notes;
                $existingConfig->save();

                $config = $existingConfig;
                $action = 'mise à jour';
            } else {
                // Créer une nouvelle config
                $config = PaymentConfig::create([
                    'provider' => $data['provider'] ?? 'easypay',
                    'mode' => $data['mode'],
                    'cid' => $data['cid'], // Sera chiffré automatiquement
                    'publishable_key' => $data['publishable_key'], // Sera chiffré automatiquement
                    'active' => $data['active'] ?? true,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'notes' => $data['notes'] ?? null,
                ]);

                $action = 'création';
            }

            // Logger l'action
            Log::info('Configuration des paiements modifiée par un administrateur', [
                'config_id' => $config->id_payment_config,
                'provider' => $config->provider,
                'mode' => $config->mode,
                'admin_id' => $adminId,
                'admin_email' => $request->user()->email,
                'action' => $action,
            ]);

            DB::commit();

            // Retourner la config avec les clés masquées
            return response()->json([
                'success' => true,
                'message' => 'Configuration des paiements ' . $action . ' avec succès',
                'data' => [
                    'id_payment_config' => $config->id_payment_config,
                    'provider' => $config->provider,
                    'mode' => $config->mode,
                    'cid_masked' => PaymentConfig::maskKey($config->cid),
                    'publishable_key_masked' => PaymentConfig::maskKey($config->publishable_key),
                    'active' => $config->active,
                    'notes' => $config->notes,
                    'updated_at' => $config->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour de la configuration des paiements', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'admin_id' => $request->user()->id_utilisateur ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques du dashboard admin
     * 
     * GET /api/admin/dashboard?date=YYYY-MM-DD&month=YYYY-MM
     * 
     * Paramètres optionnels:
     * - date: Filtrer par jour spécifique (format: YYYY-MM-DD)
     * - month: Filtrer par mois spécifique (format: YYYY-MM)
     * 
     * Retourne toutes les statistiques nécessaires pour le dashboard admin
     */
    public function dashboard(Request $request)
    {
        try {
            // Récupérer les paramètres de filtrage
            $filterDate = $request->input('date'); // Format: YYYY-MM-DD
            $filterMonth = $request->input('month'); // Format: YYYY-MM
            
            // Construire les conditions de filtrage pour les commandes
            $commandesQuery = Commande::where('statut', '!=', 'panier');
            
            if ($filterDate) {
                $commandesQuery->whereDate('date_commande', $filterDate);
            } elseif ($filterMonth) {
                $commandesQuery->whereYear('date_commande', substr($filterMonth, 0, 4))
                              ->whereMonth('date_commande', substr($filterMonth, 5, 2));
            }

            // 1. Nombre d'employés
            $nbEmployes = 0;
            if (Schema::hasTable('employe')) {
                $nbEmployes = DB::table('employe')
                    ->where('statut', 'actif')
                    ->count();
            }

            // 2. Récupérer les données du tableau de bord financier (vue)
            $tableauBord = DB::table('tableau_bord_financier')->first();
            
            // Nombre de plats depuis la vue
            $nbPlats = $tableauBord && isset($tableauBord->total_plats) 
                ? $tableauBord->total_plats 
                : MenuItem::where('statut_disponibilite', true)->count();
            
            // Chiffre d'affaires total
            $caTotal = (float) ($tableauBord && isset($tableauBord->ca_total) ? $tableauBord->ca_total : 0);
            
            // Dépenses totales
            $depensesTotal = (float) ($tableauBord && isset($tableauBord->depenses_total) ? $tableauBord->depenses_total : 0);
            
            // Profit net total
            $profitNetTotal = (float) ($tableauBord && isset($tableauBord->profit_net_total) ? $tableauBord->profit_net_total : 0);

            // 3. Nombre d'utilisateurs actifs
            $nbUtilisateursActifs = Utilisateur::where('statut_compte', 'actif')->count();

            // 4. Commandes du jour (avec filtre si spécifié)
            if ($filterDate) {
                $commandesJour = (clone $commandesQuery)->count();
            } elseif ($filterMonth) {
                $commandesJour = (clone $commandesQuery)->count();
            } else {
                $commandesJour = Commande::whereDate('date_commande', today())
                    ->where('statut', '!=', 'panier')
                    ->count();
            }

            // 5. Top 10 plats par nombre de commandes (utiliser profits_par_plat)
            $topPlatsQuery = DB::table('profits_par_plat')
                ->select('id_menuitem', 'nom', DB::raw('COALESCE(nombre_commandes, 0) as commandes'))
                ->orderBy('nombre_commandes', 'desc')
                ->limit(10);
            
            // Si filtre par date ou mois, filtrer les commandes
            if ($filterDate || $filterMonth) {
                // Pour filtrer, on doit joindre avec les commandes
                $topPlatsQuery = DB::table('profits_par_plat')
                    ->join('commande_articles', 'profits_par_plat.id_menuitem', '=', 'commande_articles.id_menuitem')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commandes.statut', '!=', 'panier');
                
                if ($filterDate) {
                    $topPlatsQuery->whereDate('commandes.date_commande', $filterDate);
                } elseif ($filterMonth) {
                    $topPlatsQuery->whereYear('commandes.date_commande', substr($filterMonth, 0, 4))
                                 ->whereMonth('commandes.date_commande', substr($filterMonth, 5, 2));
                }
                
                $topPlatsQuery->select(
                        'profits_par_plat.id_menuitem',
                        'profits_par_plat.nom',
                        DB::raw('COUNT(DISTINCT commandes.id_commande) as commandes')
                    )
                    ->groupBy('profits_par_plat.id_menuitem', 'profits_par_plat.nom')
                    ->orderBy('commandes', 'desc')
                    ->limit(10);
            }
            
            $topPlats = $topPlatsQuery->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id_menuitem,
                        'nom' => $item->nom,
                        'commandes' => (int) ($item->commandes ?? 0),
                    ];
                });

            // 6. Données mensuelles (profits et dépenses) - 12 derniers mois
            // Optimisation : utiliser une seule requête avec GROUP BY au lieu de 12 requêtes
            $monthlyData = [];
            $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            
            // Calculer la date de début (12 mois en arrière)
            $dateDebut = now()->subMonths(11)->startOfMonth();
            $dateFin = now()->endOfMonth();
            
            // Profits : une seule requête avec GROUP BY pour tous les mois
            $profitsParMois = DB::table('commande_articles')
                ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                ->whereBetween('commandes.date_commande', [$dateDebut, $dateFin])
                ->where('commandes.statut', '!=', 'panier')
                ->where('commandes.statut', '!=', 'annulee')
                ->select(
                    DB::raw("DATE_TRUNC('month', commandes.date_commande)::date as mois"),
                    DB::raw('COALESCE(SUM(commande_articles.sous_total), 0) as profits')
                )
                ->groupBy(DB::raw("DATE_TRUNC('month', commandes.date_commande)"))
                ->get()
                ->keyBy(function ($item) {
                    return (int) \Carbon\Carbon::parse($item->mois)->month;
                });
            
            // Si pas de profits depuis commande_articles, utiliser montant_total des commandes
            if ($profitsParMois->isEmpty()) {
                $profitsParMois = Commande::whereBetween('date_commande', [$dateDebut, $dateFin])
                    ->where('statut', '!=', 'panier')
                    ->where('statut', '!=', 'annulee')
                    ->select(
                        DB::raw("DATE_TRUNC('month', date_commande)::date as mois"),
                        DB::raw('COALESCE(SUM(montant_total), 0) as profits')
                    )
                    ->groupBy(DB::raw("DATE_TRUNC('month', date_commande)"))
                    ->get()
                    ->keyBy(function ($item) {
                        return (int) \Carbon\Carbon::parse($item->mois)->month;
                    });
            }
            
            // Dépenses : une seule requête avec GROUP BY pour tous les mois
            $expensesParMois = collect();
            if (Schema::hasTable('depense')) {
                // Détecter la colonne de date une seule fois
                $columns = Schema::getColumnListing('depense');
                $dateColumn = null;
                foreach (['date_depenses', 'date_depense', 'date', 'created_at', 'date_creation'] as $col) {
                    if (in_array($col, $columns)) {
                        $dateColumn = $col;
                        break;
                    }
                }
                
                \Log::info('=== Calcul des dépenses mensuelles ===', [
                    'dateColumn' => $dateColumn,
                    'dateDebut' => $dateDebut->format('Y-m-d'),
                    'dateFin' => $dateFin->format('Y-m-d'),
                    'columns' => $columns,
                ]);
                
                if ($dateColumn) {
                    // Vérifier d'abord combien de dépenses existent dans la période
                    $totalDepenses = DB::table('depense')
                        ->whereBetween($dateColumn, [$dateDebut, $dateFin])
                        ->count();
                    
                    $totalMontant = DB::table('depense')
                        ->whereBetween($dateColumn, [$dateDebut, $dateFin])
                        ->sum('montant');
                    
                    \Log::info('Dépenses trouvées dans la période', [
                        'total_depenses' => $totalDepenses,
                        'total_montant' => $totalMontant,
                        'dateColumn' => $dateColumn,
                    ]);
                    
                    // Vérifier les dépenses par type
                    $depensesParType = DB::table('depense')
                        ->whereBetween($dateColumn, [$dateDebut, $dateFin])
                        ->select('type_depense', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant) as total'))
                        ->groupBy('type_depense')
                        ->get();
                    
                    \Log::info('Dépenses par type', ['depenses_par_type' => $depensesParType]);
                    
                    $expensesParMois = DB::table('depense')
                        ->whereBetween($dateColumn, [$dateDebut, $dateFin])
                        ->select(
                            DB::raw("DATE_TRUNC('month', {$dateColumn})::date as mois"),
                            DB::raw('COALESCE(SUM(montant), 0) as expenses')
                        )
                        ->groupBy(DB::raw("DATE_TRUNC('month', {$dateColumn})"))
                        ->get()
                        ->keyBy(function ($item) {
                            return (int) \Carbon\Carbon::parse($item->mois)->month;
                        });
                    
                    \Log::info('Dépenses par mois calculées', [
                        'expensesParMois' => $expensesParMois->toArray(),
                        'count' => $expensesParMois->count(),
                    ]);
                } else {
                    \Log::warning('Colonne de date introuvable dans la table depense', [
                        'columns' => $columns,
                    ]);
                }
            } else {
                \Log::warning('La table depense n\'existe pas');
            }
            
            // Construire le tableau mensuel
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $moisNum = $date->month;
                
                $profitItem = $profitsParMois->get($moisNum);
                $expenseItem = $expensesParMois->get($moisNum);
                
                $profits = $profitItem ? (float) $profitItem->profits : 0;
                $expenses = $expenseItem ? (float) $expenseItem->expenses : 0;
                
                // Log pour déboguer
                if ($i == 11) { // Seulement pour le mois actuel
                    \Log::info("Mois actuel ({$moisLabels[$moisNum - 1]})", [
                        'moisNum' => $moisNum,
                        'profitItem' => $profitItem,
                        'expenseItem' => $expenseItem,
                        'profits' => $profits,
                        'expenses' => $expenses,
                    ]);
                }
                
                $monthlyData[] = [
                    'mois' => $moisLabels[$moisNum - 1],
                    'profits' => $profits,
                    'expenses' => $expenses,
                ];
            }
            
            \Log::info('=== Données mensuelles finales ===', [
                'monthlyData' => $monthlyData,
                'total_expenses' => array_sum(array_column($monthlyData, 'expenses')),
            ]);

            // 7. Données de stock (valeur inventaire)
            // Pour un restaurant, la "valeur inventaire" représente :
            // - Valeur totale : valeur potentielle de tous les plats disponibles (prix de vente)
            // - Valeur vendue : chiffre d'affaires réalisé (somme des commandes livrées)
            // - Taux de rotation : pourcentage montrant l'efficacité de vente
            
            // Valeur totale de l'inventaire = somme des prix de tous les plats disponibles
            // Cette valeur représente le potentiel de vente si tous les plats étaient vendus
            $valeurInventaireTotal = MenuItem::where('statut_disponibilite', true)
                ->sum('prix');
            
            // Valeur vendue = chiffre d'affaires total (somme des commandes livrées)
            $valeurVendue = $caTotal;
            
            // Taux de rotation : pourcentage de la valeur inventaire qui a été vendue
            // Note: Ce taux peut dépasser 100% car les plats peuvent être vendus plusieurs fois
            $tauxRotation = $valeurInventaireTotal > 0 
                ? round(($valeurVendue / $valeurInventaireTotal) * 100, 2) 
                : 0;
            
            // Valeur restante : représente la valeur potentielle encore disponible
            // Pour un restaurant, c'est la valeur des plats encore disponibles à la vente
            // (pas une soustraction directe, mais plutôt la valeur actuelle de l'inventaire)
            $valeurRestante = (float) $valeurInventaireTotal;
            
            $stockData = [
                'total' => [
                    'value' => (float) $valeurInventaireTotal,
                    'percentage' => 100,
                ],
                'sold' => [
                    'value' => (float) $valeurVendue,
                    'percentage' => min(100, $tauxRotation), // Limiter à 100% pour l'affichage visuel
                ],
                'remaining' => (float) $valeurRestante,
            ];

            // 8. Répartition des statuts (utiliser analyse_rentabilite)
            $platsRentables = (int) ($tableauBord && isset($tableauBord->plats_rentables) ? $tableauBord->plats_rentables : 0);
            $platsDeficitaires = (int) ($tableauBord && isset($tableauBord->plats_deficitaires) ? $tableauBord->plats_deficitaires : 0);
            $totalPlatsAnalyse = $platsRentables + $platsDeficitaires;
            
            $statusLabels = ['Rentables', 'Déficitaires', 'Équilibre'];
            $statusValues = [
                $totalPlatsAnalyse > 0 ? round(($platsRentables / $totalPlatsAnalyse) * 100) : 0,
                $totalPlatsAnalyse > 0 ? round(($platsDeficitaires / $totalPlatsAnalyse) * 100) : 0,
                0, // Équilibre
            ];
            
            // Top plats rentables depuis la vue
            $topPlatsRentables = [];
            if ($tableauBord && isset($tableauBord->top_plats_rentables) && $tableauBord->top_plats_rentables) {
                // top_plats_rentables est un array PostgreSQL
                $topPlatsArray = is_string($tableauBord->top_plats_rentables) 
                    ? json_decode($tableauBord->top_plats_rentables, true) 
                    : $tableauBord->top_plats_rentables;
                
                if (is_array($topPlatsArray)) {
                    $topPlatsRentables = array_slice($topPlatsArray, 0, 3);
                }
            }
            
            // Dépenses par type depuis la vue
            $depensesParType = [];
            if ($tableauBord && isset($tableauBord->depenses_par_type) && $tableauBord->depenses_par_type) {
                $depensesParType = is_string($tableauBord->depenses_par_type)
                    ? json_decode($tableauBord->depenses_par_type, true)
                    : $tableauBord->depenses_par_type;
            }

            // 9. Activités récentes (dernières commandes, nouveaux utilisateurs, etc.)
            $recentActivities = [];
            
            // Dernières commandes (avec filtre si spécifié)
            $recentOrdersQuery = Commande::where('statut', '!=', 'panier')
                ->orderBy('date_commande', 'desc')
                ->limit(5);
            
            if ($filterDate) {
                $recentOrdersQuery->whereDate('date_commande', $filterDate);
            } elseif ($filterMonth) {
                $recentOrdersQuery->whereYear('date_commande', substr($filterMonth, 0, 4))
                                  ->whereMonth('date_commande', substr($filterMonth, 5, 2));
            }
            
            $recentOrders = $recentOrdersQuery->with('utilisateur')->get();
            
            foreach ($recentOrders as $order) {
                $recentActivities[] = [
                    'id' => $order->id_commande,
                    'icon' => '📦',
                    'description' => "Nouvelle commande - #{$order->numero_commande}",
                    'time' => $order->date_commande->diffForHumans(),
                    'status' => $order->statut === 'livree' ? 'completed' : ($order->statut === 'annulee' ? 'failed' : 'pending'),
                    'statusText' => ucfirst(str_replace('_', ' ', $order->statut)),
                ];
            }

            // Nouveaux utilisateurs (derniers 3)
            $recentUsers = Utilisateur::orderBy('date_inscription', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentUsers as $user) {
                $recentActivities[] = [
                    'id' => 'user_' . $user->id_utilisateur,
                    'icon' => '👥',
                    'description' => "Nouvel utilisateur - {$user->nom} {$user->prenom}",
                    'time' => $user->date_inscription->diffForHumans(),
                    'status' => 'completed',
                    'statusText' => 'Actif',
                ];
            }

            // Trier par date et limiter à 4
            usort($recentActivities, function($a, $b) {
                return strcmp($b['time'], $a['time']);
            });
            $recentActivities = array_slice($recentActivities, 0, 4);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'stockTotal' => $nbEmployes, // Nombre d'employés
                        'productionTotal' => $nbPlats,
                        'utilisateursActifs' => $nbUtilisateursActifs,
                        'commandesJour' => $commandesJour,
                    ],
                    'financial' => [
                        'caTotal' => $caTotal,
                        'depensesTotal' => $depensesTotal,
                        'profitNetTotal' => $profitNetTotal,
                        'platsRentables' => $platsRentables,
                        'platsDeficitaires' => $platsDeficitaires,
                        'topPlatsRentables' => $topPlatsRentables,
                        'depensesParType' => $depensesParType,
                    ],
                    'stockData' => $stockData,
                    'statusLabels' => $statusLabels,
                    'statusValues' => $statusValues,
                    'topPlats' => $topPlats,
                    'monthlyData' => $monthlyData,
                    'recentActivities' => $recentActivities,
                    'filters' => [
                        'date' => $filterDate,
                        'month' => $filterMonth,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques du dashboard', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques détaillées pour la page Statistiques
     * 
     * GET /api/admin/statistics
     * 
     * Retourne :
     * - KPIs (CA mois, nouveaux clients, commandes, taux de conversion)
     * - CA par catégorie (pour graphique pie)
     * - Évolution CA 12 mois (pour graphique line)
     * - Commandes par semaine (4 dernières semaines)
     * - Top plats
     * - Points d'attention
     */
    public function getStatistics(Request $request)
    {
        try {
            // Vérifier que les tables existent
            if (!Schema::hasTable('commandes') || !Schema::hasTable('commande_articles') || !Schema::hasTable('utilisateur')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tables manquantes dans la base de données',
                ], 500);
            }
            
            $now = now();
            $moisActuel = $now->copy()->startOfMonth();
            $moisActuelFin = $now->copy()->endOfMonth();
            
            // 1. Chiffre d'affaires du mois actuel
            $caMois = DB::table('commande_articles')
                ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                ->where('commandes.statut', '!=', 'panier')
                ->where('commandes.statut', '!=', 'annulee')
                ->whereBetween('commandes.date_commande', [$moisActuel->format('Y-m-d H:i:s'), $moisActuelFin->format('Y-m-d H:i:s')])
                ->sum('commande_articles.sous_total');
            
            $caMois = $caMois ? (float) $caMois : 0;
            
            // 2. Nouveaux clients (mois actuel)
            $nouveauxClients = 0;
            if (Schema::hasColumn('utilisateur', 'date_inscription')) {
                $nouveauxClients = DB::table('utilisateur')
                    ->where('role', 'etudiant')
                    ->whereBetween('date_inscription', [$moisActuel->format('Y-m-d'), $moisActuelFin->format('Y-m-d')])
                    ->count();
            }
            
            // 3. Commandes du mois actuel
            $commandesMois = DB::table('commandes')
                ->where('statut', '!=', 'panier')
                ->where('statut', '!=', 'annulee')
                ->whereBetween('date_commande', [$moisActuel->format('Y-m-d H:i:s'), $moisActuelFin->format('Y-m-d H:i:s')])
                ->count();
            
            // 4. Taux de conversion (visiteurs -> commandes)
            // Approximation : nombre de commandes / nombre d'utilisateurs actifs
            $utilisateursActifs = DB::table('utilisateur')
                ->where('statut_compte', 'actif')
                ->count();
            $tauxConversion = $utilisateursActifs > 0 
                ? round(($commandesMois / $utilisateursActifs) * 100, 1)
                : 0;
            
            // 5. CA par catégorie (pour graphique pie)
            // Si pas de données pour le mois actuel, prendre toutes les données disponibles
            $caParCategorie = [];
            if (Schema::hasTable('categorie') && Schema::hasTable('menu_item')) {
                // Essayer d'abord avec le mois actuel
                $caParCategorie = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->join('menu_item', 'commande_articles.id_menuitem', '=', 'menu_item.id_menuitem')
                    ->join('categorie', 'menu_item.id_categorie', '=', 'categorie.id_categorie')
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->whereBetween('commandes.date_commande', [$moisActuel->format('Y-m-d H:i:s'), $moisActuelFin->format('Y-m-d H:i:s')])
                    ->select('categorie.nom as categorie', DB::raw('SUM(commande_articles.sous_total) as total'))
                    ->groupBy('categorie.nom')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->categorie,
                            'value' => (float) $item->total
                        ];
                    })
                    ->toArray();
                
                // Si pas de données pour le mois actuel, prendre toutes les données disponibles
                if (empty($caParCategorie)) {
                    \Log::info('Aucune donnée CA par catégorie pour le mois actuel, récupération de toutes les données');
                    $caParCategorie = DB::table('commande_articles')
                        ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                        ->join('menu_item', 'commande_articles.id_menuitem', '=', 'menu_item.id_menuitem')
                        ->join('categorie', 'menu_item.id_categorie', '=', 'categorie.id_categorie')
                        ->where('commandes.statut', '!=', 'panier')
                        ->where('commandes.statut', '!=', 'annulee')
                        ->select('categorie.nom as categorie', DB::raw('SUM(commande_articles.sous_total) as total'))
                        ->groupBy('categorie.nom')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'label' => $item->categorie,
                                'value' => (float) $item->total
                            ];
                        })
                        ->toArray();
                }
                
                \Log::info('CA par catégorie récupéré', [
                    'count' => count($caParCategorie),
                    'data' => $caParCategorie
                ]);
            }
            
            // 6. Évolution CA 12 derniers mois
            $evolutionCA = [];
            for ($i = 11; $i >= 0; $i--) {
                $mois = $now->copy()->subMonths($i);
                $debutMois = $mois->copy()->startOfMonth();
                $finMois = $mois->copy()->endOfMonth();
                
                $ca = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->whereBetween('commandes.date_commande', [$debutMois->format('Y-m-d H:i:s'), $finMois->format('Y-m-d H:i:s')])
                    ->sum('commande_articles.sous_total');
                
                $evolutionCA[] = [
                    'mois' => $mois->format('F'),
                    'mois_court' => $mois->format('M'),
                    'ca' => $ca ? (float) $ca : 0
                ];
            }
            
            // 7. Commandes par semaine (4 dernières semaines)
            $commandesParSemaine = [];
            for ($i = 3; $i >= 0; $i--) {
                $semaine = $now->copy()->subWeeks($i);
                $debutSemaine = $semaine->copy()->startOfWeek();
                $finSemaine = $semaine->copy()->endOfWeek();
                
                $count = DB::table('commandes')
                    ->where('statut', '!=', 'panier')
                    ->where('statut', '!=', 'annulee')
                    ->whereBetween('date_commande', [$debutSemaine->format('Y-m-d H:i:s'), $finSemaine->format('Y-m-d H:i:s')])
                    ->count();
                
                $commandesParSemaine[] = [
                    'semaine' => 'Semaine ' . (4 - $i),
                    'count' => (int) $count
                ];
            }
            
            // 8. Top plats (par nombre de commandes)
            $topPlats = [];
            if (Schema::hasTable('menu_item')) {
                $topPlats = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->join('menu_item', 'commande_articles.id_menuitem', '=', 'menu_item.id_menuitem')
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->whereBetween('commandes.date_commande', [$moisActuel->copy()->subMonth()->format('Y-m-d H:i:s'), $moisActuelFin->format('Y-m-d H:i:s')])
                    ->select('menu_item.nom', DB::raw('COUNT(DISTINCT commandes.id_commande) as commandes'))
                    ->groupBy('menu_item.nom')
                    ->orderBy('commandes', 'desc')
                    ->limit(4)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'nom' => $item->nom,
                            'commandes' => (int) $item->commandes
                        ];
                    })
                    ->toArray();
            }
            
            // 9. Points d'attention (plats avec marge faible)
            $pointsAttention = [];
            
            // Plats avec marge bénéficiaire < 10%
            if (Schema::hasTable('menu_item') && Schema::hasTable('depense')) {
                $platsFaibleMarge = DB::table('menu_item')
                    ->where('statut_disponibilite', true)
                    ->get();
                
                foreach ($platsFaibleMarge as $plat) {
                    $ca = DB::table('commande_articles')
                        ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                        ->where('commande_articles.id_menuitem', $plat->id_menuitem)
                        ->where('commandes.statut', '!=', 'panier')
                        ->where('commandes.statut', '!=', 'annulee')
                        ->sum('commande_articles.sous_total');
                    
                    $depenses = DB::table('depense')
                        ->where('plat_id', $plat->id_menuitem)
                        ->sum('montant');
                    
                    $ca = $ca ? (float) $ca : 0;
                    $depenses = $depenses ? (float) $depenses : 0;
                    
                    if ($ca > 0) {
                        $marge = (($ca - $depenses) / $ca) * 100;
                        if ($marge < 10 && $marge >= 0) {
                            $pointsAttention[] = "{$plat->nom} — Marge faible (" . round($marge, 1) . "%) à améliorer";
                        }
                    }
                }
            }
            
            // Limiter à 3 points d'attention
            $pointsAttention = array_slice($pointsAttention, 0, 3);
            
            // Si pas assez de points, ajouter des suggestions génériques
            if (count($pointsAttention) < 3) {
                $suggestions = [
                    'Heure creuse 15h-17h sous-exploitée',
                    'Fidélisation clients <30 jours faible'
                ];
                $pointsAttention = array_merge($pointsAttention, array_slice($suggestions, 0, 3 - count($pointsAttention)));
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'revenue' => [
                            'valeur' => (float) $caMois,
                            'max' => (float) ($caMois * 1.5), // Objectif : 50% de plus
                            'valeurDisplay' => number_format($caMois, 0, ',', ' ') . ' CDF',
                            'maxDisplay' => number_format($caMois * 1.5, 0, ',', ' ') . ' CDF',
                            'percentage' => round(($caMois / ($caMois * 1.5)) * 100)
                        ],
                        'newClients' => [
                            'valeur' => $nouveauxClients,
                            'max' => 500,
                            'valeurDisplay' => (string) $nouveauxClients,
                            'maxDisplay' => '500',
                            'percentage' => round(($nouveauxClients / 500) * 100)
                        ],
                        'orders' => [
                            'valeur' => $commandesMois,
                            'max' => 2000,
                            'valeurDisplay' => (string) $commandesMois,
                            'maxDisplay' => '2,000',
                            'percentage' => round(($commandesMois / 2000) * 100)
                        ],
                        'conversion' => [
                            'valeur' => $tauxConversion,
                            'max' => 100,
                            'valeurDisplay' => $tauxConversion . '%',
                            'maxDisplay' => '100%',
                            'percentage' => $tauxConversion
                        ]
                    ],
                    'caParCategorie' => $caParCategorie,
                    'evolutionCA' => $evolutionCA,
                    'commandesParSemaine' => $commandesParSemaine,
                    'topPlats' => $topPlats,
                    'pointsAttention' => $pointsAttention
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des statistiques', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() . ' (ligne ' . $e->getLine() . ')' : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Créer automatiquement les dépenses de salaires dans la table depense
     * à partir des salaires des employés actifs dans la table employe
     * 
     * POST /api/admin/depenses/create-salaires
     * 
     * Paramètres optionnels :
     * - month: Mois spécifique (format: YYYY-MM), par défaut mois actuel
     * - year: Année complète (format: YYYY), crée pour tous les mois de l'année
     */
    public function createSalaryExpenses(Request $request)
    {
        try {
            // Vérifier que la table depense existe
            if (!Schema::hasTable('depense')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La table depense n\'existe pas',
                ], 404);
            }

            // Vérifier que la table employe existe
            if (!Schema::hasTable('employe')) {
                return response()->json([
                    'success' => false,
                    'message' => 'La table employe n\'existe pas',
                ], 404);
            }

            // Calculer le total des salaires des employés actifs
            $totalSalaires = DB::table('employe')
                ->where('statut', 'actif')
                ->sum('salaire');

            if ($totalSalaires == 0 || $totalSalaires === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun employé actif avec un salaire trouvé',
                    'data' => [
                        'total_salaires' => 0,
                        'nb_employes_actifs' => DB::table('employe')->where('statut', 'actif')->count(),
                    ],
                ], 400);
            }

            $nbEmployesActifs = DB::table('employe')->where('statut', 'actif')->count();
            $depensesCreees = [];
            $depensesExistant = [];

            // Option 1 : Créer pour une année complète
            if ($request->has('year')) {
                $year = $request->input('year');
                $dateDebut = \Carbon\Carbon::create($year, 1, 1);
                $dateFin = \Carbon\Carbon::create($year, 12, 31);

                $current = $dateDebut->copy();
                while ($current->lte($dateFin)) {
                    $result = $this->createSalaryExpenseForMonth($current, $totalSalaires);
                    if ($result['created']) {
                        $depensesCreees[] = $result;
                    } else {
                        $depensesExistant[] = $result;
                    }
                    $current->addMonth();
                }

                return response()->json([
                    'success' => true,
                    'message' => "Dépenses de salaires créées pour l'année {$year}",
                    'data' => [
                        'total_salaires' => (float) $totalSalaires,
                        'nb_employes_actifs' => $nbEmployesActifs,
                        'annee' => $year,
                        'depenses_creees' => count($depensesCreees),
                        'depenses_existantes' => count($depensesExistant),
                        'details' => [
                            'creees' => $depensesCreees,
                            'existantes' => $depensesExistant,
                        ],
                    ],
                ], 200);
            }

            // Option 2 : Créer pour un mois spécifique
            if ($request->has('month')) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth();
            } else {
                // Option 3 : Par défaut, créer pour le mois actuel
                $date = \Carbon\Carbon::now()->startOfMonth();
            }

            $result = $this->createSalaryExpenseForMonth($date, $totalSalaires);

            if ($result['created']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dépense de salaire créée avec succès',
                    'data' => [
                        'total_salaires' => (float) $totalSalaires,
                        'nb_employes_actifs' => $nbEmployesActifs,
                        'mois' => $result['mois'],
                        'montant' => $result['montant'],
                        'date_depenses' => $result['date_depenses'],
                    ],
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Une dépense de salaire existe déjà pour ce mois',
                    'data' => [
                        'total_salaires' => (float) $totalSalaires,
                        'nb_employes_actifs' => $nbEmployesActifs,
                        'mois' => $result['mois'],
                        'montant_existant' => $result['montant'],
                        'date_depenses' => $result['date_depenses'],
                    ],
                ], 200);
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création des dépenses de salaires', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des dépenses de salaires',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Vérifier les dépenses dans la base de données (pour déboguer)
     * GET /api/admin/depenses/check
     */
    public function checkExpenses(Request $request)
    {
        try {
            $result = [
                'table_exists' => Schema::hasTable('depense'),
                'total_depenses' => 0,
                'depenses_par_type' => [],
                'depenses_par_mois' => [],
                'depenses_recentes' => [],
            ];

            if (Schema::hasTable('depense')) {
                $result['total_depenses'] = DB::table('depense')->count();
                $result['total_montant'] = DB::table('depense')->sum('montant');
                
                // Dépenses par type
                $result['depenses_par_type'] = DB::table('depense')
                    ->select('type_depense', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant) as total'))
                    ->groupBy('type_depense')
                    ->get();
                
                // Dépenses par mois (12 derniers mois)
                $dateDebut = now()->subMonths(11)->startOfMonth();
                $dateFin = now()->endOfMonth();
                
                $columns = Schema::getColumnListing('depense');
                $dateColumn = null;
                foreach (['date_depenses', 'date_depense', 'date', 'created_at', 'date_creation'] as $col) {
                    if (in_array($col, $columns)) {
                        $dateColumn = $col;
                        break;
                    }
                }
                
                if ($dateColumn) {
                    $result['date_column'] = $dateColumn;
                    $result['depenses_par_mois'] = DB::table('depense')
                        ->whereBetween($dateColumn, [$dateDebut, $dateFin])
                        ->select(
                            DB::raw("DATE_TRUNC('month', {$dateColumn})::date as mois"),
                            DB::raw('COUNT(*) as count'),
                            DB::raw('SUM(montant) as total')
                        )
                        ->groupBy(DB::raw("DATE_TRUNC('month', {$dateColumn})"))
                        ->orderBy('mois', 'desc')
                        ->get();
                }
                
                // 10 dépenses les plus récentes
                $result['depenses_recentes'] = DB::table('depense')
                    ->orderBy('date_depenses', 'desc')
                    ->limit(10)
                    ->get(['id_depense', 'plat_id', 'montant', 'type_depense', 'date_depenses', 'description']);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification des dépenses', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des dépenses',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Créer une dépense de salaire pour un mois spécifique
     */
    private function createSalaryExpenseForMonth(\Carbon\Carbon $date, $montant)
    {
        $dateDebut = $date->copy()->startOfMonth();
        $moisLabel = $date->format('F Y');
        $dateDepenses = $dateDebut->format('Y-m-d');

        // Vérifier si la dépense existe déjà
        $existing = DB::table('depense')
            ->where('type_depense', 'salaire')
            ->where('date_depenses', $dateDepenses)
            ->first();

        if ($existing) {
            return [
                'created' => false,
                'mois' => $moisLabel,
                'date_depenses' => $dateDepenses,
                'montant' => (float) $existing->montant,
            ];
        }

        // Créer la dépense
        DB::table('depense')->insert([
            'plat_id' => null,
            'montant' => $montant,
            'type_depense' => 'salaire',
            'description' => 'Salaires du mois de ' . $moisLabel,
            'date_depenses' => $dateDepenses,
            'creation' => now(),
        ]);

        return [
            'created' => true,
            'mois' => $moisLabel,
            'date_depenses' => $dateDepenses,
            'montant' => (float) $montant,
        ];
    }

    // ==================== GESTION DU MENU (ADMIN) ====================
    
    /**
     * Liste tous les plats du menu (admin)
     * GET /api/admin/menu
     */
    public function listMenuItems(Request $request)
    {
        try {
            $menuItems = MenuItem::with('categorie')
                                  ->orderBy('date_creation', 'desc')
                                  ->get();
            
            return response()->json([
                'success' => true,
                'data' => $menuItems,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des plats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau plat (admin)
     * POST /api/admin/menu
     */
    public function createMenuItem(CreateMenuItemRequest $request)
    {
        try {
            $data = $request->validated();
            
            $menuItem = MenuItem::create($data);
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat ajouté avec succès',
                'data' => $menuItem->load('categorie'),
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier un plat (admin)
     * PUT /api/admin/menu/{id}
     */
    public function updateMenuItem(UpdateMenuItemRequest $request, $id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            $data = $request->validated();
            $menuItem->update($data);
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat modifié avec succès',
                'data' => $menuItem->load('categorie'),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un plat (admin)
     * DELETE /api/admin/menu/{id}
     */
    public function deleteMenuItem($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            
            $platNom = $menuItem->nom;
            $menuItem->delete();
            
            // Invalider tous les caches du menu
            $this->clearMenuCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Plat supprimé avec succès',
                'data' => [
                    'plat_supprime' => $platNom,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulter un plat spécifique (admin)
     * GET /api/admin/menu/{id}
     */
    public function showMenuItem($id)
    {
        try {
            $menuItem = MenuItem::with('categorie')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $menuItem,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plat non trouvé',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du plat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== GESTION DES CATÉGORIES (ADMIN) ====================
    
    /**
     * Liste toutes les catégories (admin)
     * GET /api/admin/categories
     */
    public function listCategories()
    {
        try {
            $categories = Categorie::orderBy('nom')->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des catégories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle catégorie (admin)
     * POST /api/admin/categories
     */
    public function createCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:100|unique:categories,nom',
                'description' => 'nullable|string',
            ]);
            
            $category = Categorie::create($validated);
            
            // Invalider le cache des catégories
            Cache::forget('categories_list');
            
            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => $category,
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier une catégorie (admin)
     * PUT /api/admin/categories/{id}
     */
    public function updateCategory(Request $request, $id)
    {
        try {
            $category = Categorie::findOrFail($id);
            
            $validated = $request->validate([
                'nom' => 'required|string|max:100|unique:categories,nom,' . $id . ',id_categorie',
                'description' => 'nullable|string',
            ]);
            
            $category->update($validated);
            
            // Invalider le cache des catégories
            Cache::forget('categories_list');
            
            return response()->json([
                'success' => true,
                'message' => 'Catégorie modifiée avec succès',
                'data' => $category,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la catégorie',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une catégorie (admin)
     * DELETE /api/admin/categories/{id}
     */
    public function deleteCategory($id)
    {
        try {
            $category = Categorie::findOrFail($id);
            
            // Vérifier s'il y a des plats dans cette catégorie
            $platsCount = MenuItem::where('id_categorie', $id)->count();
            if ($platsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette catégorie car elle contient ' . $platsCount . ' plat(s)',
                ], 400);
            }
            
            $categoryNom = $category->nom;
            $category->delete();
            
            // Invalider le cache des catégories
            Cache::forget('categories_list');
            
            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès',
                'data' => [
                    'categorie_supprimee' => $categoryNom,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la catégorie',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== GESTION DES PROMOTIONS (ADMIN) ====================
    
    /**
     * Liste toutes les promotions (admin)
     * GET /api/admin/promotions
     */
    public function listPromotions()
    {
        try {
            $promotions = Promotion::with(['plats', 'createur'])
                                  ->orderBy('date_debut', 'desc')
                                  ->get();
            
            return response()->json([
                'success' => true,
                'data' => $promotions,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des promotions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle promotion (admin)
     * POST /api/admin/promotions
     */
    public function createPromotion(Request $request)
    {
        try {
            // Normaliser le statut avant validation (convertir les chaînes vides en null)
            $requestData = $request->all();
            if (isset($requestData['statut']) && $requestData['statut'] === '') {
                $requestData['statut'] = null;
                $request->merge($requestData);
            }
            
            $validated = $request->validate([
                'titre' => 'required|string|max:255',
                'type_promotion' => 'required|in:pourcentage,montant_fixe,offre_speciale,menu_special',
                'valeur' => 'required|numeric|min:0',
                'valeur_minimum_panier' => 'nullable|numeric|min:0',
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after:date_debut',
                'details' => 'nullable|string',
                'code_promo' => 'required|string|max:50|unique:promotion,code_promo',
                'utilisations_max' => 'nullable|integer|min:1',
                'image_url' => 'nullable|string|max:255',
                'statut' => 'nullable|in:active,inactive,expiree,epuisee',
            ]);
            
            $validated['createur_id'] = $request->user()->id_utilisateur;
            // S'assurer que le statut est toujours défini (par défaut 'active')
            if (empty($validated['statut']) || !in_array($validated['statut'], ['active', 'inactive', 'expiree', 'epuisee'])) {
                $validated['statut'] = 'active';
            }
            
            $promotion = Promotion::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Promotion créée avec succès',
                'data' => $promotion,
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier une promotion (admin)
     * PUT /api/admin/promotions/{id}
     */
    public function updatePromotion(Request $request, $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            
            // Normaliser le statut avant validation (convertir les chaînes vides en null)
            $requestData = $request->all();
            if (isset($requestData['statut']) && $requestData['statut'] === '') {
                $requestData['statut'] = null;
                $request->merge($requestData);
            }
            
            $validated = $request->validate([
                'titre' => 'sometimes|required|string|max:255',
                'type_promotion' => 'sometimes|required|in:pourcentage,montant_fixe,offre_speciale,menu_special',
                'valeur' => 'sometimes|required|numeric|min:0',
                'valeur_minimum_panier' => 'nullable|numeric|min:0',
                'date_debut' => 'sometimes|required|date',
                'date_fin' => 'sometimes|required|date|after:date_debut',
                'details' => 'nullable|string',
                'code_promo' => 'sometimes|required|string|max:50|unique:promotion,code_promo,' . $id . ',id_promo',
                'utilisations_max' => 'nullable|integer|min:1',
                'image_url' => 'nullable|string|max:255',
                'statut' => 'nullable|in:active,inactive,expiree,epuisee',
            ]);
            
            // Si le statut est fourni mais invalide, ne pas le mettre à jour (garder l'ancien)
            if (isset($validated['statut']) && !in_array($validated['statut'], ['active', 'inactive', 'expiree', 'epuisee'])) {
                unset($validated['statut']);
            }
            
            $promotion->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Promotion modifiée avec succès',
                'data' => $promotion,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion non trouvée',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une promotion (admin)
     * DELETE /api/admin/promotions/{id}
     */
    public function deletePromotion($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            
            $promotionTitre = $promotion->titre;
            $promotion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Promotion supprimée avec succès',
                'data' => [
                    'promotion_supprimee' => $promotionTitre,
                ],
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion non trouvée',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publier une promotion sur un plat (admin)
     * POST /api/admin/promotions/{promotionId}/publish
     */
    public function publishPromotion(Request $request, $promotionId)
    {
        try {
            $promotion = Promotion::findOrFail($promotionId);
            
            $validated = $request->validate([
                'id_menuitem' => 'required|integer|exists:menu_item,id_menuitem',
                'prix_promotionnel' => 'required|numeric|min:0',
            ]);
            
            // Vérifier si la promotion est déjà publiée sur ce plat
            $existing = PromoMenuItem::where('id_promo', $promotionId)
                                     ->where('id_menuitem', $validated['id_menuitem'])
                                     ->first();
            
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette promotion est déjà publiée sur ce plat',
                ], 400);
            }
            
            $promoMenuItem = PromoMenuItem::create([
                'id_promo' => $promotionId,
                'id_menuitem' => $validated['id_menuitem'],
                'prix_promotionnel' => $validated['prix_promotionnel'],
                'statut' => 'active', // Statut actif par défaut lors de la publication
                'date_debut' => $promotion->date_debut, // Utiliser les dates de la promotion
                'date_fin' => $promotion->date_fin,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Promotion publiée avec succès',
                'data' => $promoMenuItem->load(['menuItem', 'promotion']),
            ], 201);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion non trouvée',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retirer une promotion d'un plat (admin)
     * DELETE /api/admin/promotions/{promotionId}/unpublish/{menuItemId}
     */
    public function unpublishPromotion($promotionId, $menuItemId)
    {
        try {
            $promoMenuItem = PromoMenuItem::where('id_promo', $promotionId)
                                         ->where('id_menuitem', $menuItemId)
                                         ->firstOrFail();
            
            $promoMenuItem->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Promotion retirée avec succès',
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion non trouvée sur ce plat',
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait de la promotion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Méthode utilitaire pour invalider les caches du menu
     */
    private function clearMenuCache()
    {
        Cache::forget('categories_list');
        Cache::forget('plats_du_jour');
        // Invalider les caches de menu (on ne peut pas faire de pattern matching avec tous les drivers)
        // Les caches seront régénérés lors des prochaines requêtes
    }

    // ==================== GESTION DES RÉCLAMATIONS ====================

    /**
     * Lister toutes les réclamations (pour admin)
     * 
     * GET /api/admin/reclamations
     * 
     * Query parameters:
     * - statut: ouverte, assignee, resolue (optionnel)
     * - priorite: faible, moyenne, elevee, urgente (optionnel)
     * - search: recherche dans sujet/description (optionnel)
     * - sort_by: date_reclamation, priorite (défaut: date_reclamation)
     * - sort_order: asc, desc (défaut: desc)
     * - limit: nombre d'éléments par page (défaut: 20)
     * - offset: offset pour la pagination (défaut: 0)
     */
    public function listReclamations(Request $request)
    {
        try {
            $query = \App\Models\Reclamation::with(['utilisateur', 'commande', 'employeTraitant']);

            // Filtrage par statut
            if ($request->has('statut')) {
                $statut = $request->input('statut');
                $statutsValides = ['ouverte', 'assignee', 'resolue'];
                if (in_array($statut, $statutsValides)) {
                    $query->where('statut_reclamation', $statut);
                }
            }

            // Filtrage par priorité
            if ($request->has('priorite')) {
                $priorite = $request->input('priorite');
                $prioritesValides = ['faible', 'moyenne', 'elevee', 'urgente'];
                if (in_array($priorite, $prioritesValides)) {
                    $query->where('priorite', $priorite);
                }
            }

            // Recherche dans sujet/description
            if ($request->has('search') && $request->input('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('sujet', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->input('sort_by', 'date_reclamation');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSortFields = ['date_reclamation', 'priorite'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date_reclamation';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = (int) $request->input('limit', 20);
            $offset = (int) $request->input('offset', 0);
            
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            // Compter le total avant pagination
            $total = $query->count();

            // Appliquer la pagination
            $reclamations = $query->limit($limit)->offset($offset)->get();

            // Formater les données pour la réponse
            $reclamationsData = $reclamations->map(function ($reclamation) {
                return [
                    'id_reclamation' => $reclamation->id_reclamation,
                    'sujet' => $reclamation->sujet,
                    'description' => $reclamation->description,
                    'type_reclamation' => $reclamation->type_reclamation,
                    'priorite' => $reclamation->priorite,
                    'statut_reclamation' => $reclamation->statut_reclamation,
                    'reponse_employe' => $reclamation->reponse_employe,
                    'date_reclamation' => $reclamation->date_reclamation ? $reclamation->date_reclamation->format('Y-m-d H:i:s') : null,
                    'date_modification' => $reclamation->date_modification ? $reclamation->date_modification->format('Y-m-d H:i:s') : null,
                    'date_fermeture' => $reclamation->date_fermeture ? $reclamation->date_fermeture->format('Y-m-d H:i:s') : null,
                    'satisfaction_client' => $reclamation->satisfaction_client,
                    'utilisateur' => $reclamation->utilisateur ? [
                        'id_utilisateur' => $reclamation->utilisateur->id_utilisateur,
                        'nom' => $reclamation->utilisateur->nom,
                        'prenom' => $reclamation->utilisateur->prenom,
                        'email' => $reclamation->utilisateur->email,
                        'telephone' => $reclamation->utilisateur->telephone,
                    ] : null,
                    'commande' => $reclamation->commande ? [
                        'id_commande' => $reclamation->commande->id_commande,
                        'numero_commande' => $reclamation->commande->numero_commande,
                        'statut' => $reclamation->commande->statut,
                    ] : null,
                    'employe_traitant' => $reclamation->employeTraitant ? [
                        'id_utilisateur' => $reclamation->employeTraitant->id_utilisateur,
                        'nom' => $reclamation->employeTraitant->nom,
                        'prenom' => $reclamation->employeTraitant->prenom,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Réclamations récupérées avec succès',
                'data' => [
                    'reclamations' => $reclamationsData,
                    'meta' => [
                        'total' => $total,
                        'count' => $reclamations->count(),
                        'offset' => $offset,
                        'limit' => $limit,
                        'has_more' => ($offset + $limit) < $total,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des réclamations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réclamations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une réclamation spécifique (pour admin)
     * 
     * GET /api/admin/reclamations/{id}
     */
    public function getReclamation($id)
    {
        try {
            $reclamation = \App\Models\Reclamation::with(['utilisateur', 'commande', 'employeTraitant'])
                ->find($id);

            if (!$reclamation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réclamation non trouvée',
                ], 404);
            }

            // Formater les données pour la réponse
            $reclamationData = [
                'id_reclamation' => $reclamation->id_reclamation,
                'sujet' => $reclamation->sujet,
                'description' => $reclamation->description,
                'type_reclamation' => $reclamation->type_reclamation,
                'priorite' => $reclamation->priorite,
                'statut_reclamation' => $reclamation->statut_reclamation,
                'reponse_employe' => $reclamation->reponse_employe,
                'date_reclamation' => $reclamation->date_reclamation ? $reclamation->date_reclamation->format('Y-m-d H:i:s') : null,
                'date_modification' => $reclamation->date_modification ? $reclamation->date_modification->format('Y-m-d H:i:s') : null,
                'date_fermeture' => $reclamation->date_fermeture ? $reclamation->date_fermeture->format('Y-m-d H:i:s') : null,
                'satisfaction_client' => $reclamation->satisfaction_client,
                'utilisateur' => $reclamation->utilisateur ? [
                    'id_utilisateur' => $reclamation->utilisateur->id_utilisateur,
                    'nom' => $reclamation->utilisateur->nom,
                    'prenom' => $reclamation->utilisateur->prenom,
                    'email' => $reclamation->utilisateur->email,
                    'telephone' => $reclamation->utilisateur->telephone,
                ] : null,
                'commande' => $reclamation->commande ? [
                    'id_commande' => $reclamation->commande->id_commande,
                    'numero_commande' => $reclamation->commande->numero_commande,
                    'statut' => $reclamation->commande->statut,
                    'montant_total' => $reclamation->commande->montant_total,
                ] : null,
                'employe_traitant' => $reclamation->employeTraitant ? [
                    'id_utilisateur' => $reclamation->employeTraitant->id_utilisateur,
                    'nom' => $reclamation->employeTraitant->nom,
                    'prenom' => $reclamation->employeTraitant->prenom,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Réclamation récupérée avec succès',
                'data' => $reclamationData,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la réclamation', [
                'error' => $e->getMessage(),
                'id_reclamation' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la réclamation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une réclamation (statut, assignation, réponse) (pour admin)
     * 
     * PUT /api/admin/reclamations/{id}
     */
    public function updateReclamation(Request $request, $id)
    {
        try {
            $reclamation = \App\Models\Reclamation::find($id);

            if (!$reclamation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réclamation non trouvée',
                ], 404);
            }

            $validated = $request->validate([
                'statut_reclamation' => 'sometimes|in:ouverte,assignee,resolue',
                'priorite' => 'sometimes|in:faible,moyenne,elevee,urgente',
                'id_employe_traitant' => 'nullable|integer|exists:utilisateur,id_utilisateur',
                'reponse_employe' => 'nullable|string|max:2000',
            ]);

            // Mettre à jour les champs fournis
            if (isset($validated['statut_reclamation'])) {
                $reclamation->statut_reclamation = $validated['statut_reclamation'];
                
                // Si on marque comme résolue, mettre la date de fermeture
                if ($validated['statut_reclamation'] === 'resolue' && !$reclamation->date_fermeture) {
                    $reclamation->date_fermeture = now();
                }
            }

            if (isset($validated['priorite'])) {
                $reclamation->priorite = $validated['priorite'];
            }

            if (isset($validated['id_employe_traitant'])) {
                $reclamation->id_employe_traitant = $validated['id_employe_traitant'];
                
                // Si on assigne un employé, changer le statut en "assignee" si c'est encore "ouverte"
                if ($reclamation->statut_reclamation === 'ouverte') {
                    $reclamation->statut_reclamation = 'assignee';
                }
            }

            if (isset($validated['reponse_employe'])) {
                $reclamation->reponse_employe = $validated['reponse_employe'];
            }

            // Toujours mettre à jour la date de modification
            $reclamation->date_modification = now();
            $reclamation->save();

            // Charger les relations pour la réponse
            $reclamation->load(['utilisateur', 'commande', 'employeTraitant']);

            // Formater les données pour la réponse
            $reclamationData = [
                'id_reclamation' => $reclamation->id_reclamation,
                'sujet' => $reclamation->sujet,
                'description' => $reclamation->description,
                'type_reclamation' => $reclamation->type_reclamation,
                'priorite' => $reclamation->priorite,
                'statut_reclamation' => $reclamation->statut_reclamation,
                'reponse_employe' => $reclamation->reponse_employe,
                'date_reclamation' => $reclamation->date_reclamation ? $reclamation->date_reclamation->format('Y-m-d H:i:s') : null,
                'date_modification' => $reclamation->date_modification ? $reclamation->date_modification->format('Y-m-d H:i:s') : null,
                'date_fermeture' => $reclamation->date_fermeture ? $reclamation->date_fermeture->format('Y-m-d H:i:s') : null,
                'utilisateur' => $reclamation->utilisateur ? [
                    'id_utilisateur' => $reclamation->utilisateur->id_utilisateur,
                    'nom' => $reclamation->utilisateur->nom,
                    'prenom' => $reclamation->utilisateur->prenom,
                    'email' => $reclamation->utilisateur->email,
                ] : null,
                'employe_traitant' => $reclamation->employeTraitant ? [
                    'id_utilisateur' => $reclamation->employeTraitant->id_utilisateur,
                    'nom' => $reclamation->employeTraitant->nom,
                    'prenom' => $reclamation->employeTraitant->prenom,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Réclamation mise à jour avec succès',
                'data' => $reclamationData,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de la réclamation', [
                'error' => $e->getMessage(),
                'id_reclamation' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la réclamation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== ANALYSE DE RENTABILITÉ ====================

    /**
     * Récupérer l'analyse de rentabilité par plat (pour admin)
     * 
     * GET /api/admin/rentabilite
     * 
     * Query parameters:
     * - search: recherche par nom de plat (optionnel)
     * - statut: Rentable, Déficitaire, Équilibre (optionnel)
     * - sort_by: profit_net, chiffre_affaires_total, marge_beneficiaire (défaut: profit_net)
     * - sort_order: asc, desc (défaut: desc)
     */
    public function getRentabilite(Request $request)
    {
        try {
            // Récupérer tous les plats disponibles
            $menuItems = MenuItem::where('statut_disponibilite', true)->get();

            $analyses = [];

            foreach ($menuItems as $menuItem) {
                // 1. Calculer le chiffre d'affaires total (somme des sous_total des commande_articles)
                $chiffreAffaires = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commande_articles.id_menuitem', $menuItem->id_menuitem)
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->sum('commande_articles.sous_total');

                // 2. Calculer le nombre de ventes (quantité totale vendue)
                $totalQuantiteVendue = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commande_articles.id_menuitem', $menuItem->id_menuitem)
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->sum('commande_articles.quantite');

                // 3. Calculer le nombre de commandes distinctes
                $nombreCommandes = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commande_articles.id_menuitem', $menuItem->id_menuitem)
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->distinct('commandes.id_commande')
                    ->count('commandes.id_commande');

                // 4. Calculer le nombre de ventes (nombre de fois que le plat a été commandé)
                $nombreVentes = DB::table('commande_articles')
                    ->join('commandes', 'commande_articles.id_commande', '=', 'commandes.id_commande')
                    ->where('commande_articles.id_menuitem', $menuItem->id_menuitem)
                    ->where('commandes.statut', '!=', 'panier')
                    ->where('commandes.statut', '!=', 'annulee')
                    ->count();

                // 5. Calculer les dépenses totales pour ce plat
                $depensesTotal = DB::table('depense')
                    ->where('plat_id', $menuItem->id_menuitem)
                    ->sum('montant');

                // 6. Calculer le nombre de dépenses
                $nombreDepenses = DB::table('depense')
                    ->where('plat_id', $menuItem->id_menuitem)
                    ->count();

                // 7. Calculer la dépense moyenne
                $moyenneDepense = $nombreDepenses > 0 
                    ? (float) ($depensesTotal / $nombreDepenses)
                    : 0;

                // 8. Calculer le profit net
                $profitNet = (float) $chiffreAffaires - (float) $depensesTotal;

                // 9. Calculer la marge bénéficiaire (%)
                $margeBeneficiaire = (float) $chiffreAffaires > 0
                    ? round((($profitNet / (float) $chiffreAffaires) * 100), 2)
                    : 0;

                // 10. Déterminer le statut de rentabilité
                $statutRentabilite = 'Équilibre';
                if ($profitNet > 0 && $margeBeneficiaire > 10) {
                    $statutRentabilite = 'Rentable';
                } elseif ($profitNet < 0 || $margeBeneficiaire < -5) {
                    $statutRentabilite = 'Déficitaire';
                }

                // 11. Prix moyen réel (prix moyen auquel le plat a été vendu)
                $prixMoyenReel = $totalQuantiteVendue > 0
                    ? (float) ($chiffreAffaires / $totalQuantiteVendue)
                    : (float) $menuItem->prix;

                $analyses[] = [
                    'id_menuitem' => $menuItem->id_menuitem,
                    'nom' => $menuItem->nom,
                    'prix' => (float) $menuItem->prix,
                    'prix_moyen_reel' => $prixMoyenReel,
                    'total_quantite_vendue' => (int) $totalQuantiteVendue,
                    'nombre_ventes' => (int) $nombreVentes,
                    'nombre_commandes' => (int) $nombreCommandes,
                    'chiffre_affaires_total' => (float) $chiffreAffaires,
                    'depenses_total' => (float) $depensesTotal,
                    'nombre_depenses' => (int) $nombreDepenses,
                    'moyenne_depense' => $moyenneDepense,
                    'profit_net' => $profitNet,
                    'marge_beneficiaire' => $margeBeneficiaire,
                    'statut_rentabilite' => $statutRentabilite,
                ];
            }

            // Appliquer les filtres
            if ($request->has('search') && $request->input('search')) {
                $search = strtolower($request->input('search'));
                $analyses = array_filter($analyses, function ($analyse) use ($search) {
                    return strpos(strtolower($analyse['nom']), $search) !== false;
                });
            }

            if ($request->has('statut') && $request->input('statut')) {
                $statut = $request->input('statut');
                $analyses = array_filter($analyses, function ($analyse) use ($statut) {
                    return $analyse['statut_rentabilite'] === $statut;
                });
            }

            // Appliquer le tri
            $sortBy = $request->input('sort_by', 'profit_net');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['profit_net', 'chiffre_affaires_total', 'marge_beneficiaire'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'profit_net';
            }

            usort($analyses, function ($a, $b) use ($sortBy, $sortOrder) {
                $valueA = $a[$sortBy] ?? 0;
                $valueB = $b[$sortBy] ?? 0;
                
                if ($sortOrder === 'asc') {
                    return $valueA <=> $valueB;
                } else {
                    return $valueB <=> $valueA;
                }
            });

            // Réindexer le tableau après filtrage
            $analyses = array_values($analyses);

            return response()->json([
                'success' => true,
                'message' => 'Analyse de rentabilité récupérée avec succès',
                'data' => [
                    'analyses' => $analyses,
                    'meta' => [
                        'total' => count($analyses),
                        'rentables' => count(array_filter($analyses, fn($a) => $a['statut_rentabilite'] === 'Rentable')),
                        'deficitaires' => count(array_filter($analyses, fn($a) => $a['statut_rentabilite'] === 'Déficitaire')),
                        'equilibre' => count(array_filter($analyses, fn($a) => $a['statut_rentabilite'] === 'Équilibre')),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de l\'analyse de rentabilité', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'analyse de rentabilité',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
