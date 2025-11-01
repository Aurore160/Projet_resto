<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantite' => 'required|integer|min:1|max:99',
        ];
    }

    public function messages(): array
    {
        return [
            'quantite.required' => 'La quantité est obligatoire',
            'quantite.integer' => 'La quantité doit être un nombre entier',
            'quantite.min' => 'La quantité minimale est 1',
            'quantite.max' => 'La quantité maximale est 99',
        ];
    }
}