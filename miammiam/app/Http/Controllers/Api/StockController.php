<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\StoreStockOutRequest;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Utilisateur;
use Psr\Log\LoggerInterface;

class StockController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Signaler une rupture de stock pour un plat
     * 
     * POST /api/stock/out
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * 
     * Body:
     * {
     *   "id_menuitem": 5,
     *   "commentaire": "Plus de stock disponible"
     * }
     */
    public function reportStockOut(StoreStockOutRequest $request)
    {
        try {
            $employe = $request->user();
            $data = $request->validated();
            
            $this->logger->info('Signalement de rupture de stock', [
                'employe_id' => $employe->id_utilisateur,
                'employe_nom' => $employe->nom . ' ' . $employe->prenom,
                'id_menuitem' => $data['id_menuitem'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            // Récupérer le plat
            $menuItem = MenuItem::find($data['id_menuitem']);
            
            if (!$menuItem) {
                return $this->errorResponse('Plat non trouvé', 404);
            }

            // Vérifier si le plat est déjà en rupture de stock
            if ($menuItem->statut_disponibilite === false) {
                return $this->errorResponse('Ce plat est déjà signalé comme étant en rupture de stock', 400);
            }

            // Mettre à jour le statut de disponibilité
            $menuItem->update([
                'statut_disponibilite' => false,
                'date_modification' => now(),
            ]);

            $this->logger->info('Statut de disponibilité mis à jour', [
                'id_menuitem' => $menuItem->id_menuitem,
                'nom_plat' => $menuItem->nom,
                'nouveau_statut' => false,
            ]);

            // Notifier tous les gérants actifs
            $this->notifyGerantsOfStockOut($menuItem, $employe, $data['commentaire'] ?? null);

            // Formater la réponse
            $responseData = [
                'id_menuitem' => $menuItem->id_menuitem,
                'nom' => $menuItem->nom,
                'statut_disponibilite' => $menuItem->statut_disponibilite,
                'date_modification' => $menuItem->date_modification->format('Y-m-d H:i:s'),
                'signale_par' => [
                    'id_utilisateur' => $employe->id_utilisateur,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                ],
                'commentaire' => $data['commentaire'] ?? null,
            ];

            return $this->successResponse(
                $responseData,
                'Rupture de stock signalée avec succès. Les gérants ont été notifiés.'
            );

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du signalement de rupture de stock', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'employe_id' => $request->user()->id_utilisateur ?? null,
                'id_menuitem' => $request->input('id_menuitem'),
            ]);

            return $this->handleException(
                $e,
                'Erreur lors du signalement de rupture de stock',
                [
                    'employe_id' => $request->user()->id_utilisateur ?? null,
                    'id_menuitem' => $request->input('id_menuitem'),
                ]
            );
        }
    }

    /**
     * Notifier tous les gérants d'une rupture de stock
     * 
     * @param MenuItem $menuItem
     * @param Utilisateur $employe
     * @param string|null $commentaire
     * @return void
     */
    private function notifyGerantsOfStockOut(MenuItem $menuItem, Utilisateur $employe, ?string $commentaire = null)
    {
        try {
            // Récupérer tous les gérants actifs
            $gerants = Utilisateur::where('role', 'gerant')
                ->where('statut_compte', 'actif')
                ->get();

            $nombreNotifies = 0;

            // Construire le message de notification
            $nomEmploye = trim(($employe->prenom ?? '') . ' ' . ($employe->nom ?? ''));
            if (empty($nomEmploye)) {
                $nomEmploye = $employe->email ?? 'Un employé';
            }

            $message = "Rupture de stock signalée pour le plat \"{$menuItem->nom}\" par {$nomEmploye}.";
            if ($commentaire) {
                $message .= " Commentaire: {$commentaire}";
            }

            foreach ($gerants as $gerant) {
                try {
                    // Créer une notification pour chaque gérant
                    Notification::create([
                        'id_utilisateur' => $gerant->id_utilisateur,
                        'id_commande' => null, // Pas de commande associée
                        'type_notification' => 'system',
                        'titre' => 'Rupture de stock',
                        'message' => $message,
                        'lu' => false,
                        'date_creation' => now(),
                    ]);
                    
                    $nombreNotifies++;
                } catch (\Exception $e) {
                    // Log l'erreur pour ce gérant mais continue avec les autres
                    $this->logger->error('Erreur lors de la notification d\'un gérant', [
                        'gerant_id' => $gerant->id_utilisateur,
                        'menu_item_id' => $menuItem->id_menuitem,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('Notifications gérants envoyées pour rupture de stock', [
                'menu_item_id' => $menuItem->id_menuitem,
                'nom_plat' => $menuItem->nom,
                'gerants_notifies' => $nombreNotifies,
                'total_gerants' => $gerants->count(),
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer le signalement de rupture de stock
            $this->logger->error('Erreur lors de la notification des gérants', [
                'menu_item_id' => $menuItem->id_menuitem ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiResponses;
use App\Http\Requests\StoreStockOutRequest;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Utilisateur;
use Psr\Log\LoggerInterface;

class StockController extends Controller
{
    use HandlesApiResponses;
    
    protected $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Signaler une rupture de stock pour un plat
     * 
     * POST /api/stock/out
     * 
     * Accessible uniquement aux employés (employe, gerant, admin)
     * 
     * Body:
     * {
     *   "id_menuitem": 5,
     *   "commentaire": "Plus de stock disponible"
     * }
     */
    public function reportStockOut(StoreStockOutRequest $request)
    {
        try {
            $employe = $request->user();
            $data = $request->validated();
            
            $this->logger->info('Signalement de rupture de stock', [
                'employe_id' => $employe->id_utilisateur,
                'employe_nom' => $employe->nom . ' ' . $employe->prenom,
                'id_menuitem' => $data['id_menuitem'],
                'commentaire' => $data['commentaire'] ?? null,
            ]);

            // Récupérer le plat
            $menuItem = MenuItem::find($data['id_menuitem']);
            
            if (!$menuItem) {
                return $this->errorResponse('Plat non trouvé', 404);
            }

            // Vérifier si le plat est déjà en rupture de stock
            if ($menuItem->statut_disponibilite === false) {
                return $this->errorResponse('Ce plat est déjà signalé comme étant en rupture de stock', 400);
            }

            // Mettre à jour le statut de disponibilité
            $menuItem->update([
                'statut_disponibilite' => false,
                'date_modification' => now(),
            ]);

            $this->logger->info('Statut de disponibilité mis à jour', [
                'id_menuitem' => $menuItem->id_menuitem,
                'nom_plat' => $menuItem->nom,
                'nouveau_statut' => false,
            ]);

            // Notifier tous les gérants actifs
            $this->notifyGerantsOfStockOut($menuItem, $employe, $data['commentaire'] ?? null);

            // Formater la réponse
            $responseData = [
                'id_menuitem' => $menuItem->id_menuitem,
                'nom' => $menuItem->nom,
                'statut_disponibilite' => $menuItem->statut_disponibilite,
                'date_modification' => $menuItem->date_modification->format('Y-m-d H:i:s'),
                'signale_par' => [
                    'id_utilisateur' => $employe->id_utilisateur,
                    'nom' => $employe->nom,
                    'prenom' => $employe->prenom,
                ],
                'commentaire' => $data['commentaire'] ?? null,
            ];

            return $this->successResponse(
                $responseData,
                'Rupture de stock signalée avec succès. Les gérants ont été notifiés.'
            );

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du signalement de rupture de stock', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'employe_id' => $request->user()->id_utilisateur ?? null,
                'id_menuitem' => $request->input('id_menuitem'),
            ]);

            return $this->handleException(
                $e,
                'Erreur lors du signalement de rupture de stock',
                [
                    'employe_id' => $request->user()->id_utilisateur ?? null,
                    'id_menuitem' => $request->input('id_menuitem'),
                ]
            );
        }
    }

    /**
     * Notifier tous les gérants d'une rupture de stock
     * 
     * @param MenuItem $menuItem
     * @param Utilisateur $employe
     * @param string|null $commentaire
     * @return void
     */
    private function notifyGerantsOfStockOut(MenuItem $menuItem, Utilisateur $employe, ?string $commentaire = null)
    {
        try {
            // Récupérer tous les gérants actifs
            $gerants = Utilisateur::where('role', 'gerant')
                ->where('statut_compte', 'actif')
                ->get();

            $nombreNotifies = 0;

            // Construire le message de notification
            $nomEmploye = trim(($employe->prenom ?? '') . ' ' . ($employe->nom ?? ''));
            if (empty($nomEmploye)) {
                $nomEmploye = $employe->email ?? 'Un employé';
            }

            $message = "Rupture de stock signalée pour le plat \"{$menuItem->nom}\" par {$nomEmploye}.";
            if ($commentaire) {
                $message .= " Commentaire: {$commentaire}";
            }

            foreach ($gerants as $gerant) {
                try {
                    // Créer une notification pour chaque gérant
                    Notification::create([
                        'id_utilisateur' => $gerant->id_utilisateur,
                        'id_commande' => null, // Pas de commande associée
                        'type_notification' => 'system',
                        'titre' => 'Rupture de stock',
                        'message' => $message,
                        'lu' => false,
                        'date_creation' => now(),
                    ]);
                    
                    $nombreNotifies++;
                } catch (\Exception $e) {
                    // Log l'erreur pour ce gérant mais continue avec les autres
                    $this->logger->error('Erreur lors de la notification d\'un gérant', [
                        'gerant_id' => $gerant->id_utilisateur,
                        'menu_item_id' => $menuItem->id_menuitem,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('Notifications gérants envoyées pour rupture de stock', [
                'menu_item_id' => $menuItem->id_menuitem,
                'nom_plat' => $menuItem->nom,
                'gerants_notifies' => $nombreNotifies,
                'total_gerants' => $gerants->count(),
            ]);

        } catch (\Exception $e) {
            // Log l'erreur mais ne fait pas échouer le signalement de rupture de stock
            $this->logger->error('Erreur lors de la notification des gérants', [
                'menu_item_id' => $menuItem->id_menuitem ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

