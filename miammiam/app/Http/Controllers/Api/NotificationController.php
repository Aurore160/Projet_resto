<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\SendNotificationToEmployeesRequest;
use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class NotificationController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Lister toutes les notifications de l'utilisateur connecté
     * 
     * GET /api/notifications
     * 
     * Paramètres optionnels :
     * - unread_only : Si true, retourne seulement les notifications non lues (défaut: false)
     * - limit : Nombre de notifications à retourner (défaut: toutes)
     * - type : Filtrer par type de notification (commande, system, promotion)
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }
            
            // Paramètres optionnels
            $unreadOnly = $request->query('unread_only', false);
            $limit = $request->query('limit');
            $type = $request->query('type');
            
            // Requête de base avec gestion d'erreur de connexion DB
            try {
                $query = Notification::where('id_utilisateur', $utilisateur->id_utilisateur);
            } catch (\Illuminate\Database\QueryException $e) {
                // Erreur de connexion à la base de données
                $this->logger->error('Erreur de connexion à la base de données lors de la récupération des notifications', [
                    'error' => $e->getMessage(),
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                ]);
                return $this->errorResponse('Erreur de connexion à la base de données. Veuillez réessayer plus tard.', 503);
            }
            
            // Filtrer par non lues si demandé
            if ($unreadOnly) {
                $query->where('lu', false);
            }
            
            // Filtrer par type si fourni
            if ($type) {
                $query->where('type_notification', $type);
            }
            
            // Trier par date (plus récent en premier)
            $query->orderBy('date_creation', 'desc');
            
            // Appliquer la limite si fournie
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->take($limit);
            }
            
            $notifications = $query->get();
            
            // Formater la réponse
            $notificationsFormatees = $notifications->map(function ($notification) {
                // Formatage sécurisé des dates
                $dateCreation = null;
                if ($notification->date_creation) {
                    try {
                        $dateCreation = $notification->date_creation->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateCreation = is_string($notification->date_creation) ? $notification->date_creation : null;
                    }
                }
                
                $dateLecture = null;
                if ($notification->date_lecture) {
                    try {
                        $dateLecture = $notification->date_lecture->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateLecture = is_string($notification->date_lecture) ? $notification->date_lecture : null;
                    }
                }
                
                return [
                    'id_notification' => $notification->id_notification,
                    'type_notification' => $notification->type_notification,
                    'titre' => $notification->titre,
                    'message' => $notification->message,
                    'lu' => (bool) $notification->lu,
                    'date_creation' => $dateCreation,
                    'date_lecture' => $dateLecture,
                    'id_commande' => $notification->id_commande,
                ];
            });
            
            // Compter les notifications non lues
            $unreadCount = Notification::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('lu', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => $notificationsFormatees,
                'meta' => [
                    'total' => $notificationsFormatees->count(),
                    'unread_count' => $unreadCount,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des notifications',
                ['user_id' => $utilisateur->id_utilisateur ?? null]
            );
        }
    }

    /**
     * Marquer une notification comme lue
     * 
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la notification
            $notification = Notification::where('id_notification', $id)
                ->where('id_utilisateur', $utilisateur->id_utilisateur)
                ->first();
            
            if (!$notification) {
                return $this->notFoundResponse('Notification');
            }
            
            // Marquer comme lue
            $notification->markAsRead();
            
            return $this->successResponse([
                'id_notification' => $notification->id_notification,
                'lu' => $notification->lu,
                'date_lecture' => $notification->date_lecture ? $notification->date_lecture->format('Y-m-d H:i:s') : null,
            ], 'Notification marquée comme lue');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la mise à jour de la notification',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'notification_id' => $id ?? null,
                ]
            );
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     * 
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            // Marquer toutes les notifications non lues comme lues
            $updated = Notification::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('lu', false)
                ->update([
                    'lu' => true,
                    'date_lecture' => now(),
                ]);
            
            return $this->successResponse([
                'updated_count' => $updated,
            ], 'Toutes les notifications ont été marquées comme lues');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la mise à jour des notifications',
                ['user_id' => $utilisateur->id_utilisateur ?? null]
            );
        }
    }

    /**
     * Supprimer une notification
     * 
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $utilisateur = $request->user();
            
            // Récupérer la notification
            $notification = Notification::where('id_notification', $id)
                ->where('id_utilisateur', $utilisateur->id_utilisateur)
                ->first();
            
            if (!$notification) {
                return $this->notFoundResponse('Notification');
            }
            
            // Supprimer la notification
            $notification->delete();
            
            return $this->successResponse(null, 'Notification supprimée avec succès');
            
        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de la suppression de la notification',
                [
                    'user_id' => $utilisateur->id_utilisateur ?? null,
                    'notification_id' => $id ?? null,
                ]
            );
        }
    }

    /**
     * Récupérer les notifications pour les employés
     * 
     * GET /api/notifications/employee
     * 
     * Cette route est accessible uniquement aux employés (employe, gerant, admin).
     * Retourne les notifications liées aux commandes (nouvelles commandes, changements de statut, etc.)
     * 
     * Paramètres optionnels :
     * - unread_only : Si true, retourne seulement les notifications non lues (défaut: false)
     * - limit : Nombre de notifications à retourner (défaut: toutes)
     */
    public function employeeNotifications(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }

            // Vérifier que l'utilisateur est un employé, gérant ou admin
            $rolesAutorises = ['employe', 'gerant', 'admin'];
            if (!in_array($utilisateur->role, $rolesAutorises)) {
                return $this->errorResponse('Accès refusé. Cette route est réservée aux employés.', 403);
            }
            
            $this->logger->info('Récupération des notifications employé', [
                'user_id' => $utilisateur->id_utilisateur,
                'role' => $utilisateur->role,
            ]);
            
            // Paramètres optionnels
            $unreadOnly = $request->query('unread_only', false);
            $limit = $request->query('limit');
            
            // Requête de base : notifications de type 'commande' pour l'employé
            $query = Notification::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('type_notification', 'commande');
            
            // Filtrer par non lues si demandé
            if ($unreadOnly) {
                $query->where('lu', false);
            }
            
            // Trier par date (plus récent en premier)
            $query->orderBy('date_creation', 'desc');
            
            // Appliquer la limite si fournie
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->take($limit);
            }
            
            // Charger les relations pour avoir plus d'informations
            $query->with(['commande.utilisateur', 'commande.articles']);
            
            $notifications = $query->get();
            
            // Formater la réponse avec plus de détails pour les employés
            $notificationsFormatees = $notifications->map(function ($notification) {
                // Formatage sécurisé des dates
                $dateCreation = null;
                if ($notification->date_creation) {
                    try {
                        $dateCreation = $notification->date_creation->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateCreation = is_string($notification->date_creation) ? $notification->date_creation : null;
                    }
                }
                
                $dateLecture = null;
                if ($notification->date_lecture) {
                    try {
                        $dateLecture = $notification->date_lecture->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateLecture = is_string($notification->date_lecture) ? $notification->date_lecture : null;
                    }
                }
                
                // Informations de la commande si elle existe
                $commandeData = null;
                if ($notification->commande) {
                    $commande = $notification->commande;
                    $commandeData = [
                        'id_commande' => $commande->id_commande,
                        'numero_commande' => $commande->numero_commande,
                        'statut' => $commande->statut,
                        'montant_total' => $commande->montant_total,
                        'type_commande' => $commande->type_commande,
                        'adresse_livraison' => $commande->adresse_livraison,
                        'date_commande' => $commande->date_commande ? $commande->date_commande->format('Y-m-d H:i:s') : null,
                    ];
                    
                    // Informations du client si disponible
                    if ($commande->utilisateur) {
                        $commandeData['client'] = [
                            'id_utilisateur' => $commande->utilisateur->id_utilisateur,
                            'nom' => $commande->utilisateur->nom,
                            'prenom' => $commande->utilisateur->prenom,
                            'email' => $commande->utilisateur->email,
                            'telephone' => $commande->utilisateur->telephone,
                        ];
                    }
                    
                    // Nombre d'articles
                    if ($commande->articles) {
                        $commandeData['nombre_articles'] = $commande->articles->count();
                    }
                }
                
                return [
                    'id_notification' => $notification->id_notification,
                    'type_notification' => $notification->type_notification,
                    'titre' => $notification->titre,
                    'message' => $notification->message,
                    'lu' => (bool) $notification->lu,
                    'date_creation' => $dateCreation,
                    'date_lecture' => $dateLecture,
                    'commande' => $commandeData,
                ];
            });
            
            // Compter les notifications non lues
            $unreadCount = Notification::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('type_notification', 'commande')
                ->where('lu', false)
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => $notificationsFormatees,
                'meta' => [
                    'total' => $notificationsFormatees->count(),
                    'unread_count' => $unreadCount,
                    'role' => $utilisateur->role,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans NotificationController::employeeNotifications', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $utilisateur->id_utilisateur ?? null,
            ]);
            
            return $this->handleException(
                $e,
                'Erreur lors de la récupération des notifications employé',
                ['user_id' => $utilisateur->id_utilisateur ?? null],
                true // Inclure les détails en dev
            );
        }
    }

    /**
     * Envoyer des notifications aux employés (pour gérant/admin)
     * 
     * POST /api/notifications/employees
     */
    public function sendToEmployees(SendNotificationToEmployeesRequest $request)
    {
        try {
            $gerant = $request->user();
            $data = $request->validated();

            $this->logger->info('Tentative d\'envoi de notifications aux employés', [
                'gerant_id' => $gerant->id_utilisateur,
                'gerant_nom' => $gerant->nom . ' ' . $gerant->prenom,
                'titre' => $data['titre'],
                'type_notification' => $data['type_notification'] ?? 'system',
            ]);

            // Déterminer les destinataires
            $employes = null;
            
            if (isset($data['id_employes']) && !empty($data['id_employes'])) {
                // Envoyer à des employés spécifiques
                $employes = Utilisateur::whereIn('id_utilisateur', $data['id_employes'])
                    ->whereIn('role', ['employe', 'gerant', 'admin'])
                    ->where('statut_compte', 'actif')
                    ->get();
                
                if ($employes->isEmpty()) {
                    return $this->errorResponse('Aucun employé actif trouvé parmi les identifiants spécifiés', 404);
                }
            } else {
                // Envoyer à tous les employés actifs
                $employes = Utilisateur::whereIn('role', ['employe', 'gerant', 'admin'])
                    ->where('statut_compte', 'actif')
                    ->get();
                
                if ($employes->isEmpty()) {
                    return $this->errorResponse('Aucun employé actif trouvé', 404);
                }
            }

            // Type de notification par défaut : 'system'
            $typeNotification = $data['type_notification'] ?? 'system';

            // Créer une notification pour chaque employé
            $notificationsCrees = [];
            $nombreNotifies = 0;
            $erreurs = [];

            foreach ($employes as $employe) {
                try {
                    $notification = Notification::create([
                        'id_utilisateur' => $employe->id_utilisateur,
                        'id_commande' => null,
                        'type_notification' => $typeNotification,
                        'titre' => $data['titre'],
                        'message' => $data['message'],
                        'lu' => false,
                        'date_creation' => now(),
                    ]);

                    $notificationsCrees[] = [
                        'id_notification' => $notification->id_notification,
                        'id_utilisateur' => $employe->id_utilisateur,
                        'nom' => $employe->nom,
                        'prenom' => $employe->prenom,
                        'email' => $employe->email,
                    ];

                    $nombreNotifies++;
                } catch (\Exception $e) {
                    $erreurs[] = [
                        'id_utilisateur' => $employe->id_utilisateur,
                        'nom' => $employe->nom . ' ' . $employe->prenom,
                        'error' => $e->getMessage(),
                    ];
                    
                    $this->logger->error('Erreur lors de la création de notification pour un employé', [
                        'employe_id' => $employe->id_utilisateur,
                        'gerant_id' => $gerant->id_utilisateur,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('Notifications envoyées aux employés', [
                'gerant_id' => $gerant->id_utilisateur,
                'titre' => $data['titre'],
                'type_notification' => $typeNotification,
                'employes_notifies' => $nombreNotifies,
                'total_employes' => $employes->count(),
                'erreurs' => count($erreurs),
            ]);

            // Préparer la réponse
            $response = [
                'notifications_envoyees' => $notificationsCrees,
                'statistiques' => [
                    'total_destinataires' => $employes->count(),
                    'notifications_crees' => $nombreNotifies,
                    'erreurs' => count($erreurs),
                ],
            ];

            if (!empty($erreurs)) {
                $response['erreurs'] = $erreurs;
            }

            $message = "Notification envoyée avec succès à {$nombreNotifies} employé(s)";
            if (count($erreurs) > 0) {
                $message .= " (" . count($erreurs) . " erreur(s))";
            }

            return $this->successResponse($response, $message);

        } catch (\Exception $e) {
            return $this->handleException(
                $e,
                'Erreur lors de l\'envoi des notifications aux employés',
                [
                    'gerant_id' => $request->user()->id_utilisateur ?? null,
                    'titre' => $request->input('titre') ?? null,
                ]
            );
        }
    }
}
