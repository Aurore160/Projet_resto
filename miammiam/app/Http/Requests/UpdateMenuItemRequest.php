<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categorie' => 'sometimes|integer|exists:categories,id_categorie',
            'nom' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'prix' => 'sometimes|numeric|min:0',
            'statut_disponibilite' => 'nullable|boolean',
            'photo_url' => 'nullable|string|max:255',
            'plat_du_jour' => 'nullable|boolean',
            'temps_preparation' => 'nullable|integer|min:0',
            'ingredients' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id_categorie.exists' => 'Cette catégorie n\'existe pas',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères',
            'prix.numeric' => 'Le prix doit être un nombre',
            'prix.min' => 'Le prix doit être positif',
            'temps_preparation.min' => 'Le temps de préparation doit être positif',
        ];
    }
}
