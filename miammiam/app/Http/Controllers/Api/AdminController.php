<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\UpdatePaymentConfigRequest;
use App\Models\Utilisateur;
use App\Models\ConnexionLog;
use App\Models\PaymentConfig;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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
                    'utilisateur.telephone'
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
}
