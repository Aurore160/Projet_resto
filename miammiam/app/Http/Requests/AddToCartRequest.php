<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => 'required|integer|exists:menu_item,id_menuitem',
            'quantite' => 'required|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'menu_item_id.required' => 'Le plat est obligatoire',
            'menu_item_id.exists' => 'Ce plat n\'existe pas',
            'quantite.required' => 'La quantité est obligatoire',
            'quantite.integer' => 'La quantité doit être un nombre entier',
            'quantite.min' => 'La quantité minimale est 1',
            'quantite.max' => 'La quantité maximale est 99',
        ];
    }
}