<?php

namespace App\Strategies\Payment;

use App\Services\EasypayService;

/**
 * Stratégie de paiement pour Easypay
 */
class EasypayStrategy implements PaymentStrategyInterface
{
    protected $easypayService;
    protected $transactionReference;
    protected $redirectUrl;

    public function __construct(EasypayService $easypayService)
    {
        $this->easypayService = $easypayService;
    }

    /**
     * Initialiser un paiement avec Easypay
     * 
     * @param array $data Les données du paiement
     * @return array ['success' => bool, 'message' => string, 'reference' => string|null, 'redirect_url' => string|null]
     */
    public function initializePayment(array $data): array
    {
        try {
            $result = $this->easypayService->initializeTransaction($data);
            
            if ($result['success']) {
                $this->transactionReference = $result['reference'] ?? null;
                $this->redirectUrl = $result['redirect_url'] ?? null;
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement Easypay: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Récupérer la référence de transaction
     * 
     * @return string|null
     */
    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    /**
     * Récupérer l'URL de redirection
     * 
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }
}

