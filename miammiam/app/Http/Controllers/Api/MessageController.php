<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class MessageController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoyer un message au gérant (pour signaler un souci ou un incident)
     * 
     * POST /api/messages
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * 
     * Types de messages disponibles :
     * - signalement : Signalement d'un problème général
     * - question : Question posée au gérant
     * - urgence : Situation urgente
     * - retard : Incident de retard (livraison, préparation, etc.)
     * - erreur : Erreur dans la commande ou le service
     * - client_absent : Client absent lors de la livraison
     * - autre : Autre type de message
     * 
     * Body:
     * {
     *   "sujet": "Retard de livraison",
     *   "message": "La livraison de la commande #CMD-123 a pris du retard...",
     *   "type_message": "retard",
     *   "priorite": "haute"
     * }
     */
    public function store(StoreMessageRequest $request)
    {
        try {
            $employe = $request->user();
            $data = $request->validated();
            
            $this->logger->info('Tentative d\'envoi de message', [
                'employe_id' => $employe->id_utilisateur,
                'employe_nom' => $employe->nom . ' ' . $employe->prenom,
                'sujet' => $data['sujet'],
                'type_message' => $data['type_message'] ?? 'signalement',
            ]);

            // Récupérer le premier gérant actif (ou tous les gérants)
            $gerants = Utilisateur::where('role', 'gerant')
                ->where('statut_compte', 'actif')
                ->get();

            if ($gerants->isEmpty()) {
                return $this->errorResponse('Aucun gérant actif trouvé. Impossible d\'envoyer le message.', 404);
            }

            // Créer le message pour chaque gérant (ou seulement le premier)
            // Ici, on crée un message pour chaque gérant pour qu'ils soient tous notifiés
            $messagesCrees = [];
            
            foreach ($gerants as $gerant) {
                $message = Message::create([
                    'id_expediteur' => $employe->id_utilisateur,
                    'id_destinataire' => $gerant->id_utilisateur,
                    'sujet' => $data['sujet'],
                    'message' => $data['message'],
                    'type_message' => $data['type_message'] ?? 'signalement',
                    'priorite' => $data['priorite'] ?? 'normale',
                    'statut' => 'envoye',
                    'date_envoi' => now(),
                ]);

                $messagesCrees[] = $message;

                // Notifier le gérant
                $this->notifyGerantOfMessage($message, $employe);
            }

            $this->logger->info('Message(s) créé(s) avec succès', [
                'employe_id' => $employe->id_utilisateur,
                'nombre_messages' => count($messagesCrees),
                'gerants_notifies' => $gerants->count(),
            ]);

            // Formater la réponse avec le premier message créé
            $messageData = [
                'id_message' => $messagesCrees[0]->id_message,
                'sujet' => $messagesCrees[0]->sujet,
                'message' => $messagesCrees[0]->message,
                'type_message' => $messagesCrees[0]->type_message,
                'priorite' => $messagesCrees[0]->priorite,
                'statut' => $messagesCrees[0]->statut,
                'date_envoi' => $messagesCrees[0]->date_envoi->format('Y-m-d H:i:s'),
                'expediteur' => [
                    'id_utilisateur' => $employe->id_utilisateur,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                    'email' => $employe->email,
                ],
                'destinataires' => $gerants->map(function ($gerant) {
                    return [
                        'id_utilisateur' => $gerant->id_utilisateur,
                        'nom' => $gerant->nom,
                        'prenom' => $gerant->prenom,
                        'email' => $gerant->email,
                    ];
                }),
            ];

            return $this->createdResponse(
                $messageData,
                'Message envoyé avec succès. Les gérants ont été notifiés.'
            );

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'employe_id' => $request->user()->id_utilisateur ?? null,
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de l\'envoi du message',
                [
                    'employe_id' => $request->user()->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Récupérer l'historique des messages
     * 
     * GET /api/messages
     * 
     * Pour les employés : retourne les messages qu'ils ont envoyés
     * Pour les gérants : retourne les messages qu'ils ont reçus
     * 
     * Paramètres optionnels :
     * - type_message : Filtrer par type (signalement, question, urgence, autre)
     * - statut : Filtrer par statut (envoye, lu, repondu, resolu)
     * - priorite : Filtrer par priorité (basse, normale, haute, urgente)
     * - limit : Nombre de messages à retourner
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }

            // Vérifier que l'utilisateur est un employé, gérant ou admin
            $rolesAutorises = ['employe', 'gerant', 'admin'];
            if (!in_array($utilisateur->role, $rolesAutorises)) {
                return $this->errorResponse('Accès refusé. Cette route est réservée aux employés et gérants.', 403);
            }

            $this->logger->info('Récupération de l\'historique des messages', [
                'user_id' => $utilisateur->id_utilisateur,
                'role' => $utilisateur->role,
            ]);

            // Paramètres optionnels
            $typeMessage = $request->query('type_message');
            $statut = $request->query('statut');
            $priorite = $request->query('priorite');
            $limit = $request->query('limit');

            // Construire la requête selon le rôle
            if ($utilisateur->role === 'gerant' || $utilisateur->role === 'admin') {
                // Les gérants voient les messages qu'ils ont reçus
                $query = Message::where('id_destinataire', $utilisateur->id_utilisateur)
                    ->with(['expediteur']);
            } else {
                // Les employés voient les messages qu'ils ont envoyés
                $query = Message::where('id_expediteur', $utilisateur->id_utilisateur)
                    ->with(['destinataire']);
            }

            // Filtrer par type si fourni
            if ($typeMessage) {
                $query->where('type_message', $typeMessage);
            }

            // Filtrer par statut si fourni
            if ($statut) {
                $query->where('statut', $statut);
            }

            // Filtrer par priorité si fournie
            if ($priorite) {
                $query->where('priorite', $priorite);
            }

            // Trier par date (plus récent en premier)
            $query->orderBy('date_envoi', 'desc');

            // Appliquer la limite si fournie
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->take($limit);
            }

            $messages = $query->get();

            // Formater la réponse
            $messagesFormates = $messages->map(function ($message) use ($utilisateur) {
                $dateEnvoi = null;
                if ($message->date_envoi) {
                    try {
                        $dateEnvoi = $message->date_envoi->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateEnvoi = is_string($message->date_envoi) ? $message->date_envoi : null;
                    }
                }

                $dateLecture = null;
                if ($message->date_lecture) {
                    try {
                        $dateLecture = $message->date_lecture->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateLecture = is_string($message->date_lecture) ? $message->date_lecture : null;
                    }
                }

                $dateReponse = null;
                if ($message->date_reponse) {
                    try {
                        $dateReponse = $message->date_reponse->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateReponse = is_string($message->date_reponse) ? $message->date_reponse : null;
                    }
                }

                // Informations de l'expéditeur
                $expediteurData = null;
                if ($message->expediteur) {
                    $expediteurData = [
                        'id_utilisateur' => $message->expediteur->id_utilisateur,
                        'nom' => $message->expediteur->nom,
                        'prenom' => $message->expediteur->prenom,
                        'email' => $message->expediteur->email,
                    ];
                }

                // Informations du destinataire
                $destinataireData = null;
                if ($message->destinataire) {
                    $destinataireData = [
                        'id_utilisateur' => $message->destinataire->id_utilisateur,
                        'nom' => $message->destinataire->nom,
                        'prenom' => $message->destinataire->prenom,
                        'email' => $message->destinataire->email,
                    ];
                }

                return [
                    'id_message' => $message->id_message,
                    'sujet' => $message->sujet,
                    'message' => $message->message,
                    'type_message' => $message->type_message,
                    'priorite' => $message->priorite,
                    'statut' => $message->statut,
                    'date_envoi' => $dateEnvoi,
                    'date_lecture' => $dateLecture,
                    'date_reponse' => $dateReponse,
                    'reponse' => $message->reponse,
                    'expediteur' => $expediteurData,
                    'destinataire' => $destinataireData,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $messagesFormates,
                'meta' => [
                    'total' => $messagesFormates->count(),
                    'role' => $utilisateur->role,
                ],
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans MessageController::index', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $utilisateur->id_utilisateur ?? null,
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'historique des messages',
                ['user_id' => $utilisateur->id_utilisateur ?? null],
                true
            );
        }
    }

    /**
     * Notifier le gérant d'un nouveau message
     * 
     * @param Message $message
     * @param Utilisateur $employe
     * @return void
     */
    private function notifyGerantOfMessage(Message $message, Utilisateur $employe)
    {
        try {
            $gerant = $message->destinataire;

            // Construire le message de notification
            $nomEmploye = trim(($employe->prenom ?? '') . ' ' . ($employe->nom ?? ''));
            if (empty($nomEmploye)) {
                $nomEmploye = $employe->email ?? 'Un employé';
            }

            $messageNotification = "Nouveau message de {$nomEmploye}: {$message->sujet}";
            if ($message->priorite === 'urgente' || $message->priorite === 'haute') {
                $messageNotification .= " [PRIORITÉ: " . strtoupper($message->priorite) . "]";
            }

            // Créer une notification pour le gérant
            Notification::create([
                'id_utilisateur' => $gerant->id_utilisateur,
                'id_commande' => null, // Pas de commande associée
                'type_notification' => 'system',
                'titre' => 'Nouveau message d\'un employé',
                'message' => $messageNotification,
                'lu' => false,
                'date_creation' => now(),
            ]);

            $this->logger->info('Notification gérant envoyée pour nouveau message', [
                'message_id' => $message->id_message,
                'gerant_id' => $gerant->id_utilisateur,
                'employe_id' => $employe->id_utilisateur,
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer l'envoi du message
            $this->logger->error('Erreur lors de la notification du gérant', [
                'message_id' => $message->id_message ?? null,
                'gerant_id' => $message->destinataire->id_utilisateur ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}


class MessageController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoyer un message au gérant (pour signaler un souci ou un incident)
     * 
     * POST /api/messages
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * 
     * Types de messages disponibles :
     * - signalement : Signalement d'un problème général
     * - question : Question posée au gérant
     * - urgence : Situation urgente
     * - retard : Incident de retard (livraison, préparation, etc.)
     * - erreur : Erreur dans la commande ou le service
     * - client_absent : Client absent lors de la livraison
     * - autre : Autre type de message
     * 
     * Body:
     * {
     *   "sujet": "Retard de livraison",
     *   "message": "La livraison de la commande #CMD-123 a pris du retard...",
     *   "type_message": "retard",
     *   "priorite": "haute"
     * }
     */
    public function store(StoreMessageRequest $request)
    {
        try {
            $employe = $request->user();
            $data = $request->validated();
            
            $this->logger->info('Tentative d\'envoi de message', [
                'employe_id' => $employe->id_utilisateur,
                'employe_nom' => $employe->nom . ' ' . $employe->prenom,
                'sujet' => $data['sujet'],
                'type_message' => $data['type_message'] ?? 'signalement',
            ]);

            // Récupérer le premier gérant actif (ou tous les gérants)
            $gerants = Utilisateur::where('role', 'gerant')
                ->where('statut_compte', 'actif')
                ->get();

            if ($gerants->isEmpty()) {
                return $this->errorResponse('Aucun gérant actif trouvé. Impossible d\'envoyer le message.', 404);
            }

            // Créer le message pour chaque gérant (ou seulement le premier)
            // Ici, on crée un message pour chaque gérant pour qu'ils soient tous notifiés
            $messagesCrees = [];
            
            foreach ($gerants as $gerant) {
                $message = Message::create([
                    'id_expediteur' => $employe->id_utilisateur,
                    'id_destinataire' => $gerant->id_utilisateur,
                    'sujet' => $data['sujet'],
                    'message' => $data['message'],
                    'type_message' => $data['type_message'] ?? 'signalement',
                    'priorite' => $data['priorite'] ?? 'normale',
                    'statut' => 'envoye',
                    'date_envoi' => now(),
                ]);

                $messagesCrees[] = $message;

                // Notifier le gérant
                $this->notifyGerantOfMessage($message, $employe);
            }

            $this->logger->info('Message(s) créé(s) avec succès', [
                'employe_id' => $employe->id_utilisateur,
                'nombre_messages' => count($messagesCrees),
                'gerants_notifies' => $gerants->count(),
            ]);

            // Formater la réponse avec le premier message créé
            $messageData = [
                'id_message' => $messagesCrees[0]->id_message,
                'sujet' => $messagesCrees[0]->sujet,
                'message' => $messagesCrees[0]->message,
                'type_message' => $messagesCrees[0]->type_message,
                'priorite' => $messagesCrees[0]->priorite,
                'statut' => $messagesCrees[0]->statut,
                'date_envoi' => $messagesCrees[0]->date_envoi->format('Y-m-d H:i:s'),
                'expediteur' => [
                    'id_utilisateur' => $employe->id_utilisateur,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                    'email' => $employe->email,
                ],
                'destinataires' => $gerants->map(function ($gerant) {
                    return [
                        'id_utilisateur' => $gerant->id_utilisateur,
                        'nom' => $gerant->nom,
                        'prenom' => $gerant->prenom,
                        'email' => $gerant->email,
                    ];
                }),
            ];

            return $this->createdResponse(
                $messageData,
                'Message envoyé avec succès. Les gérants ont été notifiés.'
            );

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'employe_id' => $request->user()->id_utilisateur ?? null,
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de l\'envoi du message',
                [
                    'employe_id' => $request->user()->id_utilisateur ?? null,
                ]
            );
        }
    }

    /**
     * Récupérer l'historique des messages
     * 
     * GET /api/messages
     * 
     * Pour les employés : retourne les messages qu'ils ont envoyés
     * Pour les gérants : retourne les messages qu'ils ont reçus
     * 
     * Paramètres optionnels :
     * - type_message : Filtrer par type (signalement, question, urgence, autre)
     * - statut : Filtrer par statut (envoye, lu, repondu, resolu)
     * - priorite : Filtrer par priorité (basse, normale, haute, urgente)
     * - limit : Nombre de messages à retourner
     */
    public function index(Request $request)
    {
        try {
            $utilisateur = $request->user();
            
            if (!$utilisateur) {
                return $this->errorResponse('Non authentifié', 401);
            }

            // Vérifier que l'utilisateur est un employé, gérant ou admin
            $rolesAutorises = ['employe', 'gerant', 'admin'];
            if (!in_array($utilisateur->role, $rolesAutorises)) {
                return $this->errorResponse('Accès refusé. Cette route est réservée aux employés et gérants.', 403);
            }

            $this->logger->info('Récupération de l\'historique des messages', [
                'user_id' => $utilisateur->id_utilisateur,
                'role' => $utilisateur->role,
            ]);

            // Paramètres optionnels
            $typeMessage = $request->query('type_message');
            $statut = $request->query('statut');
            $priorite = $request->query('priorite');
            $limit = $request->query('limit');

            // Construire la requête selon le rôle
            if ($utilisateur->role === 'gerant' || $utilisateur->role === 'admin') {
                // Les gérants voient les messages qu'ils ont reçus
                $query = Message::where('id_destinataire', $utilisateur->id_utilisateur)
                    ->with(['expediteur']);
            } else {
                // Les employés voient les messages qu'ils ont envoyés
                $query = Message::where('id_expediteur', $utilisateur->id_utilisateur)
                    ->with(['destinataire']);
            }

            // Filtrer par type si fourni
            if ($typeMessage) {
                $query->where('type_message', $typeMessage);
            }

            // Filtrer par statut si fourni
            if ($statut) {
                $query->where('statut', $statut);
            }

            // Filtrer par priorité si fournie
            if ($priorite) {
                $query->where('priorite', $priorite);
            }

            // Trier par date (plus récent en premier)
            $query->orderBy('date_envoi', 'desc');

            // Appliquer la limite si fournie
            if ($limit) {
                $limit = (int) $limit;
                $limit = max(1, min(100, $limit)); // Limiter entre 1 et 100
                $query->take($limit);
            }

            $messages = $query->get();

            // Formater la réponse
            $messagesFormates = $messages->map(function ($message) use ($utilisateur) {
                $dateEnvoi = null;
                if ($message->date_envoi) {
                    try {
                        $dateEnvoi = $message->date_envoi->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateEnvoi = is_string($message->date_envoi) ? $message->date_envoi : null;
                    }
                }

                $dateLecture = null;
                if ($message->date_lecture) {
                    try {
                        $dateLecture = $message->date_lecture->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateLecture = is_string($message->date_lecture) ? $message->date_lecture : null;
                    }
                }

                $dateReponse = null;
                if ($message->date_reponse) {
                    try {
                        $dateReponse = $message->date_reponse->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $dateReponse = is_string($message->date_reponse) ? $message->date_reponse : null;
                    }
                }

                // Informations de l'expéditeur
                $expediteurData = null;
                if ($message->expediteur) {
                    $expediteurData = [
                        'id_utilisateur' => $message->expediteur->id_utilisateur,
                        'nom' => $message->expediteur->nom,
                        'prenom' => $message->expediteur->prenom,
                        'email' => $message->expediteur->email,
                    ];
                }

                // Informations du destinataire
                $destinataireData = null;
                if ($message->destinataire) {
                    $destinataireData = [
                        'id_utilisateur' => $message->destinataire->id_utilisateur,
                        'nom' => $message->destinataire->nom,
                        'prenom' => $message->destinataire->prenom,
                        'email' => $message->destinataire->email,
                    ];
                }

                return [
                    'id_message' => $message->id_message,
                    'sujet' => $message->sujet,
                    'message' => $message->message,
                    'type_message' => $message->type_message,
                    'priorite' => $message->priorite,
                    'statut' => $message->statut,
                    'date_envoi' => $dateEnvoi,
                    'date_lecture' => $dateLecture,
                    'date_reponse' => $dateReponse,
                    'reponse' => $message->reponse,
                    'expediteur' => $expediteurData,
                    'destinataire' => $destinataireData,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $messagesFormates,
                'meta' => [
                    'total' => $messagesFormates->count(),
                    'role' => $utilisateur->role,
                ],
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans MessageController::index', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $utilisateur->id_utilisateur ?? null,
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de la récupération de l\'historique des messages',
                ['user_id' => $utilisateur->id_utilisateur ?? null],
                true
            );
        }
    }

    /**
     * Notifier le gérant d'un nouveau message
     * 
     * @param Message $message
     * @param Utilisateur $employe
     * @return void
     */
    private function notifyGerantOfMessage(Message $message, Utilisateur $employe)
    {
        try {
            $gerant = $message->destinataire;

            // Construire le message de notification
            $nomEmploye = trim(($employe->prenom ?? '') . ' ' . ($employe->nom ?? ''));
            if (empty($nomEmploye)) {
                $nomEmploye = $employe->email ?? 'Un employé';
            }

            $messageNotification = "Nouveau message de {$nomEmploye}: {$message->sujet}";
            if ($message->priorite === 'urgente' || $message->priorite === 'haute') {
                $messageNotification .= " [PRIORITÉ: " . strtoupper($message->priorite) . "]";
            }

            // Créer une notification pour le gérant
            Notification::create([
                'id_utilisateur' => $gerant->id_utilisateur,
                'id_commande' => null, // Pas de commande associée
                'type_notification' => 'system',
                'titre' => 'Nouveau message d\'un employé',
                'message' => $messageNotification,
                'lu' => false,
                'date_creation' => now(),
            ]);

            $this->logger->info('Notification gérant envoyée pour nouveau message', [
                'message_id' => $message->id_message,
                'gerant_id' => $gerant->id_utilisateur,
                'employe_id' => $employe->id_utilisateur,
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer l'envoi du message
            $this->logger->error('Erreur lors de la notification du gérant', [
                'message_id' => $message->id_message ?? null,
                'gerant_id' => $message->destinataire->id_utilisateur ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
