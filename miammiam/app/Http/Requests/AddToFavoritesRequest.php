<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToFavoritesRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent ajouter un favori
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'menu_item_id' => 'required|integer|exists:menu_item,id_menuitem',
            // Pas besoin de 'id_utilisateur' car on le récupère depuis l'utilisateur connecté
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'menu_item_id.required' => 'Le plat est obligatoire',
            'menu_item_id.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'menu_item_id.exists' => 'Ce plat n\'existe pas ou n\'est plus disponible',
        ];
    }
}

     */
    public function messages(): array
    {
        return [
            'menu_item_id.required' => 'Le plat est obligatoire',
            'menu_item_id.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'menu_item_id.exists' => 'Ce plat n\'existe pas ou n\'est plus disponible',
        ];
    }
}
