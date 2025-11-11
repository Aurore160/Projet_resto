<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_commande' => 'required|in:sur_place,livraison',
            'adresse_livraison' => 'required_if:type_commande,livraison|nullable|string',
            'points_utilises' => 'nullable|integer|min:0',
            'commentaire' => 'nullable|string|max:500',
            'instructions_speciales' => 'nullable|string|max:500',
            'heure_arrivee_prevue' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'type_commande.required' => 'Le type de commande est obligatoire',
            'type_commande.in' => 'Le type de commande doit être "sur_place" ou "livraison"',
            'adresse_livraison.required_if' => 'L\'adresse de livraison est obligatoire pour les commandes à livrer',
            'points_utilises.integer' => 'Le nombre de points doit être un nombre entier',
            'points_utilises.min' => 'Le nombre de points ne peut pas être négatif',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères',
            'instructions_speciales.max' => 'Les instructions spéciales ne peuvent pas dépasser 500 caractères',
            'heure_arrivee_prevue.date' => 'L\'heure d\'arrivée prévue doit être une date valide',
        ];
    }
}
