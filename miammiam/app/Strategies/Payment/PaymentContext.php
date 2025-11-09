<?php

namespace App\Strategies\Payment;

/**
 * Contexte pour gérer les stratégies de paiement (Pattern Strategy)
 */
class PaymentContext
{
    protected $strategy;
    protected $transactionReference;
    protected $redirectUrl;

    /**
     * Définir la stratégie de paiement à utiliser
     * 
     * @param PaymentStrategyInterface $strategy
     * @return void
     */
    public function setStrategy(PaymentStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * Initialiser un paiement avec la stratégie actuelle
     * 
     * @param array $data Les données du paiement
     * @return array ['success' => bool, 'message' => string, 'reference' => string|null, 'redirect_url' => string|null]
     */
    public function initializePayment(array $data): array
    {
        if (!$this->strategy) {
            return [
                'success' => false,
                'message' => 'Aucune stratégie de paiement n\'a été définie',
            ];
        }

        $result = $this->strategy->initializePayment($data);
        
        // Stocker les informations de transaction
        if ($result['success']) {
            $this->transactionReference = $this->strategy->getTransactionReference() ?? $result['reference'] ?? null;
            $this->redirectUrl = $this->strategy->getRedirectUrl() ?? $result['redirect_url'] ?? null;
        }

        return $result;
    }

    /**
     * Récupérer la référence de transaction
     * 
     * @return string|null
     */
    public function getTransactionReference(): ?string
    {
        return $this->transactionReference ?? ($this->strategy ? $this->strategy->getTransactionReference() : null);
    }

    /**
     * Récupérer l'URL de redirection
     * 
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl ?? ($this->strategy ? $this->strategy->getRedirectUrl() : null);
    }
}

