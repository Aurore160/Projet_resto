<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categorie' => 'required|integer|exists:categories,id_categorie',
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
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
            'id_categorie.required' => 'La catégorie est obligatoire',
            'id_categorie.exists' => 'Cette catégorie n\'existe pas',
            'nom.required' => 'Le nom du plat est obligatoire',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères',
            'prix.required' => 'Le prix est obligatoire',
            'prix.numeric' => 'Le prix doit être un nombre',
            'prix.min' => 'Le prix doit être positif',
            'temps_preparation.min' => 'Le temps de préparation doit être positif',
        ];
    }
}
