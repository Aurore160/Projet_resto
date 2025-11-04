<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * La vérification du rôle (admin) se fait via le middleware 'role'
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => 'sometimes|string|in:easypay',
            'mode' => 'required|string|in:sandbox,production',
            'cid' => 'required|string|min:10',
            'publishable_key' => 'required|string|min:10',
            'active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'provider.in' => 'Le fournisseur de paiement doit être "easypay"',
            'mode.required' => 'Le mode est obligatoire',
            'mode.in' => 'Le mode doit être "sandbox" ou "production"',
            'cid.required' => 'Le Correlation ID (CID) est obligatoire',
            'cid.min' => 'Le Correlation ID doit contenir au moins 10 caractères',
            'publishable_key.required' => 'La clé publique est obligatoire',
            'publishable_key.min' => 'La clé publique doit contenir au moins 10 caractères',
            'active.boolean' => 'Le statut actif doit être vrai ou faux',
            'notes.max' => 'Les notes ne peuvent pas dépasser 500 caractères',
        ];
    }
}

            'publishable_key' => 'required|string|min:10',
            'active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'provider.in' => 'Le fournisseur de paiement doit être "easypay"',
            'mode.required' => 'Le mode est obligatoire',
            'mode.in' => 'Le mode doit être "sandbox" ou "production"',
            'cid.required' => 'Le Correlation ID (CID) est obligatoire',
            'cid.min' => 'Le Correlation ID doit contenir au moins 10 caractères',
            'publishable_key.required' => 'La clé publique est obligatoire',
            'publishable_key.min' => 'La clé publique doit contenir au moins 10 caractères',
            'active.boolean' => 'Le statut actif doit être vrai ou faux',
            'notes.max' => 'Les notes ne peuvent pas dépasser 500 caractères',
        ];
    }
}
