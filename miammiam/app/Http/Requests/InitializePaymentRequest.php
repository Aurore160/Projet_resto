<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitializePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permettre à tous les utilisateurs authentifiés
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commande_id' => 'required|integer|exists:commandes,id_commande',
            'language' => 'nullable|in:fr,en', // Optionnel : français ou anglais
            'payment_method' => 'required|in:credit_card,mobile_money', // Requis : moyen de paiement choisi
            'adresse_livraison' => 'nullable|string|max:500', // Optionnel : adresse de livraison
            'montant_total' => 'nullable|numeric|min:0', // Optionnel : montant total (pour vérification)
            'points_utilises' => 'nullable|integer|min:0', // Optionnel : points de fidélité utilisés
        ];
    }

    public function messages(): array
    {
        return [
            'commande_id.required' => 'L\'ID de la commande est obligatoire',
            'commande_id.integer' => 'L\'ID de la commande doit être un nombre entier',
            'commande_id.exists' => 'La commande spécifiée n\'existe pas',
            'language.in' => 'La langue doit être "fr" ou "en"',
            'payment_method.in' => 'Le moyen de paiement doit être "credit_card" ou "mobile_money"',
            'points_utilises.integer' => 'Le nombre de points doit être un nombre entier',
            'points_utilises.min' => 'Le nombre de points ne peut pas être négatif',
        ];
    }
}
