<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModerateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les gérants et admins peuvent modérer les avis
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Autoriser uniquement les gérants et admins
        return in_array($user->role, ['gerant', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statut_moderation' => [
                'required',
                'string',
                'in:approuve,en_attente,rejete',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'statut_moderation.required' => 'Le statut de modération est obligatoire',
            'statut_moderation.string' => 'Le statut de modération doit être une chaîne de caractères',
            'statut_moderation.in' => 'Le statut de modération doit être : approuve, en_attente ou rejete',
        ];
    }
}

    {
        return [
            'statut_moderation' => [
                'required',
                'string',
                'in:approuve,en_attente,rejete',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'statut_moderation.required' => 'Le statut de modération est obligatoire',
            'statut_moderation.string' => 'Le statut de modération doit être une chaîne de caractères',
            'statut_moderation.in' => 'Le statut de modération doit être : approuve, en_attente ou rejete',
        ];
    }
}
