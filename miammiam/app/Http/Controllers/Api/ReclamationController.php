<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\StoreReclamationRequest;
use App\Models\Reclamation;
use App\Models\Commande;
use App\Models\Utilisateur;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReclamationController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Créer une réclamation / message de contact
     * 
     * POST /api/reclamations
     * 
     * Cette route est accessible même pour les utilisateurs non connectés.
     * Si l'utilisateur est connecté, on associe automatiquement la réclamation à son compte.
     */
    public function store(StoreReclamationRequest $request)
    {
        try {
            $this->logger->info('Tentative de création de réclamation', [
                'request_data' => $request->all(),
                'user_authenticated' => $request->user() !== null,
            ]);

            $utilisateur = $request->user(); // Peut être null si non connecté
            $data = $request->validated();

            // Si l'utilisateur n'est pas connecté, créer/récupérer un utilisateur avec l'email fourni
            if (!$utilisateur) {
                // Chercher un utilisateur existant avec cet email
                $guestEmail = $data['email'];
                $utilisateur = Utilisateur::where('email', $guestEmail)->first();
                
                if (!$utilisateur) {
                    // Créer un utilisateur temporaire pour le contact (avec rôle 'etudiant' par défaut)
                    $utilisateur = Utilisateur::create([
                        'nom' => $data['nom'] ?? 'Contact',
                        'prenom' => '',
                        'email' => $guestEmail,
                        'telephone' => $data['telephone'] ?? null,
                        'mot_de_passe' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                        'role' => 'etudiant', // Utiliser le rôle par défaut
                        'statut_compte' => 'actif',
                        'points_balance' => 0,
                    ]);
                }
            }

            $this->logger->info('Données validées', [
                'data' => $data,
                'id_utilisateur' => $utilisateur->id_utilisateur,
            ]);

            // Si une commande est fournie, vérifier qu'elle existe et appartient à l'utilisateur
            $idCommande = null;
            if (isset($data['id_commande']) && $data['id_commande'] !== null) {
                $commande = Commande::where('id_commande', $data['id_commande'])
                    ->where('id_utilisateur', $utilisateur->id_utilisateur)
                    ->first();

                if (!$commande) {
                    return $this->errorResponse('Cette commande n\'existe pas ou ne vous appartient pas', 403);
                }
                
                $idCommande = $data['id_commande'];
            }

            // Construire la description avec toutes les infos de contact + message
            $description = "Nom: {$data['nom']}\n";
            $description .= "Email: {$data['email']}\n";
            if (!empty($data['telephone'])) {
                $description .= "Téléphone: {$data['telephone']}\n";
            }
            $description .= "\nMessage:\n{$data['message']}";

            // Créer la réclamation
            $this->logger->info('Création de la réclamation en base de données', [
                'data_to_insert' => [
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'id_commande' => $idCommande,
                    'sujet' => $data['sujet'],
                    'description' => $description,
                ],
            ]);

            $reclamation = Reclamation::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'id_commande' => $idCommande,
                'sujet' => $data['sujet'],
                'description' => $description,
                'type_reclamation' => 'autre',
                'priorite' => 'moyenne',
                'statut_reclamation' => 'ouverte',
                'date_reclamation' => now(),
                'date_modification' => now(),
            ]);

            // Logger la création
            $this->logger->info('Réclamation créée', [
                'id_reclamation' => $reclamation->id_reclamation,
                'sujet' => $reclamation->sujet,
                'id_utilisateur' => $reclamation->id_utilisateur,
                'id_commande' => $reclamation->id_commande,
            ]);

            // Formater la réponse
            $reclamationData = [
                'id_reclamation' => $reclamation->id_reclamation,
                'nom' => $data['nom'],
                'email' => $data['email'],
                'sujet' => $reclamation->sujet,
                'message' => $data['message'],
                'statut_reclamation' => $reclamation->statut_reclamation,
                'date_reclamation' => $reclamation->date_reclamation->format('Y-m-d H:i:s'),
            ];

            return $this->createdResponse($reclamationData, 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la réclamation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de l\'envoi de votre message',
                [
                    'email' => $request->input('email'),
                ]
            );
        }
    }
}

                'priorite' => 'moyenne',
                'statut_reclamation' => 'ouverte',
                'date_reclamation' => now(),
                'date_modification' => now(),
            ]);

            // Logger la création
            $this->logger->info('Réclamation créée', [
                'id_reclamation' => $reclamation->id_reclamation,
                'sujet' => $reclamation->sujet,
                'id_utilisateur' => $reclamation->id_utilisateur,
                'id_commande' => $reclamation->id_commande,
            ]);

            // Formater la réponse
            $reclamationData = [
                'id_reclamation' => $reclamation->id_reclamation,
                'nom' => $data['nom'],
                'email' => $data['email'],
                'sujet' => $reclamation->sujet,
                'message' => $data['message'],
                'statut_reclamation' => $reclamation->statut_reclamation,
                'date_reclamation' => $reclamation->date_reclamation->format('Y-m-d H:i:s'),
            ];

            return $this->createdResponse($reclamationData, 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la réclamation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
            ]);

            return $this->handleException(
                $e,
                'Erreur lors de l\'envoi de votre message',
                [
                    'email' => $request->input('email'),
                ]
            );
        }
    }
}
