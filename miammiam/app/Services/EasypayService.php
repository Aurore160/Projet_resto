<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class EasypayService
{
    protected $baseUrl;
    protected $cid;
    protected $publishableKey;
    protected $mode;

    public function __construct()
    {
        // Charger directement depuis .env sans passer par config() pour éviter les problèmes de cache
        $this->mode = env('EASYPAY_MODE', 'sandbox');
        $this->cid = env('EASYPAY_CID');
        $this->publishableKey = env('EASYPAY_PUBLISHABLE_KEY');
        $this->baseUrl = 'https://www.e-com-easypay.com';
        
        // Debug: logger les valeurs récupérées
        Log::debug('EasypayService - Configuration chargée', [
            'mode' => $this->mode,
            'cid_present' => !empty($this->cid),
            'cid_length' => strlen($this->cid ?? ''),
            'publishable_key_present' => !empty($this->publishableKey),
            'publishable_key_length' => strlen($this->publishableKey ?? ''),
        ]);
        
        if (empty($this->cid) || empty($this->publishableKey)) {
            Log::error('EasypayService - Clés API manquantes', [
                'cid_empty' => empty($this->cid),
                'publishable_key_empty' => empty($this->publishableKey),
            ]);
            throw new Exception('Les clés API Easypay ne sont pas configurées. Vérifiez votre fichier .env (EASYPAY_CID et EASYPAY_PUBLISHABLE_KEY)');
        }
    }

    /**
    * Initialiser une transaction de paiement avec Easypay
    * 
    * @param array $data Les données de la transaction (montant, commande, etc.)
    * @return array
    */
    public function initializeTransaction(array $data)
    {
        // Construire l'URL de l'API Easypay
        $url = "{$this->baseUrl}/{$this->mode}/payment/initialization";
        $url .= "?cid={$this->cid}&token={$this->publishableKey}";

        // Préparer les données à envoyer à Easypay
        $payload = [
            'order_ref' => $data['order_ref'],
            'currency' => $data['currency'] ?? 'CDF',
            'amount' => $data['amount'],
            'customer_name' => $data['customer_name'],
            'description' => $data['description'],
            'success_url' => $data['success_url'],
            'error_url' => $data['error_url'],
            'cancel_url' => $data['cancel_url'],
            'language' => $data['language'] ?? 'fr',
            'channels' => $data['channels'] ?? [
                ['channel' => 'CREDIT CARD'],
                ['channel' => 'MOBILE MONEY']
            ],
        ];

        // Ajouter l'email si fourni
        if (isset($data['customer_email'])) {
            $payload['customer_email'] = $data['customer_email'];
        }

        try {
            // Utiliser curl natif PHP (comme dans la documentation Easypay)
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                // Désactiver la vérification SSL pour le développement (sandbox)
                // ATTENTION: À réactiver en production avec un certificat valide
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            
            curl_close($curl);
            
            if ($curlError) {
                Log::error('Erreur CURL Easypay', [
                    'error' => $curlError,
                    'url' => $url
                ]);
                return [
                    'success' => false,
                    'message' => 'Erreur de connexion: ' . $curlError
                ];
            }
            
            $result = json_decode($response, true);
            
            // Log la réponse pour debug
            Log::debug('Easypay - Réponse initialisation', [
                'http_code' => $httpCode,
                'result' => $result
            ]);

            // Vérifier si la transaction a été initialisée avec succès
            if ($httpCode >= 200 && $httpCode < 300 && isset($result['code']) && $result['code'] == 1) {
                return [
                    'success' => true,
                    'reference' => $result['reference'],
                    'redirect_url' => "{$this->baseUrl}/{$this->mode}/payment/initialization?reference={$result['reference']}",
                    'data' => $result
                ];
            }

            // Si ça a échoué, retourner l'erreur
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'initialisation de la transaction',
                'http_code' => $httpCode,
                'data' => $result
            ];

        } catch (Exception $e) {
            // En cas d'erreur, la logger et retourner un message
            Log::error('Erreur Easypay - Initialisation transaction', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la communication avec Easypay: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'une transaction de paiement
     * 
     * @param string $reference La référence de la transaction retournée par Easypay
     * @return array
     */
    public function checkPaymentStatus(string $reference)
    {
        // Construire l'URL de l'API Easypay pour vérifier le statut
        $url = "{$this->baseUrl}/{$this->mode}/payment/{$reference}/checking-payment";

        try {
            // Utiliser curl natif PHP pour vérifier le statut
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                // Désactiver la vérification SSL pour le développement (sandbox)
                // ATTENTION: À réactiver en production avec un certificat valide
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            
            curl_close($curl);
            
            if ($curlError) {
                Log::error('Erreur CURL Easypay - Vérification statut', [
                    'error' => $curlError,
                    'reference' => $reference
                ]);
                return [
                    'success' => false,
                    'message' => 'Erreur de connexion: ' . $curlError,
                    'status' => 'error'
                ];
            }
            
            $result = json_decode($response, true);

            // Si la transaction existe (200)
            if ($httpCode === 200 && isset($result['payment'])) {
                return [
                    'success' => true,
                    'transaction' => $result['transaction'] ?? null,
                    'payment' => $result['payment'] ?? null,
                    'status' => $result['payment']['status'] ?? null, // SUCCESS, CANCELED, DECLINED
                    'channel' => $result['payment']['channel'] ?? null, // CARD, MOBILE MONEY, etc.
                    'data' => $result
                ];
            }

            // Si la transaction n'existe pas (404)
            if ($httpCode === 404) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Transaction non trouvée',
                    'status' => 'not_found'
                ];
            }

            // Erreur serveur (500) ou autre
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la vérification du statut',
                'status' => 'error',
                'http_code' => $httpCode,
                'data' => $result
            ];

        } catch (Exception $e) {
            Log::error('Erreur Easypay - Vérification statut transaction', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'reference' => $reference
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la communication avec Easypay: ' . $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
}
