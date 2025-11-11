<?php

namespace App\Strategies\Payment;

/**
 * Interface pour les stratégies de paiement
 */
interface PaymentStrategyInterface
{
    /**
     * Initialiser un paiement
     * 
     * @param array $data Les données du paiement
     * @return array ['success' => bool, 'message' => string, 'reference' => string|null, 'redirect_url' => string|null]
     */
    public function initializePayment(array $data): array;

    /**
     * Récupérer la référence de transaction
     * 
     * @return string|null
     */
    public function getTransactionReference(): ?string;

    /**
     * Récupérer l'URL de redirection
     * 
     * @return string|null
     */
    public function getRedirectUrl(): ?string;
}

