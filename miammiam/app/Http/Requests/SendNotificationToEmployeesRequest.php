<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationToEmployeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les gérants et admins peuvent envoyer des notifications aux employés
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
            'titre' => [
                'required',
                'string',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'type_notification' => [
                'nullable',
                'string',
                'in:commande,system,promotion',
            ],
            'id_employes' => [
                'nullable',
                'array',
            ],
            'id_employes.*' => [
                'integer',
                'exists:utilisateur,id_utilisateur',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire',
            'titre.string' => 'Le titre doit être une chaîne de caractères',
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.string' => 'Le message doit être une chaîne de caractères',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 1000 caractères',
            'type_notification.in' => 'Le type de notification doit être : commande, system ou promotion',
            'id_employes.array' => 'Les identifiants des employés doivent être un tableau',
            'id_employes.*.integer' => 'Chaque identifiant d\'employé doit être un entier',
            'id_employes.*.exists' => 'Un ou plusieurs employés spécifiés n\'existent pas',
        ];
    }
}

    {
        return [
            'titre' => [
                'required',
                'string',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'type_notification' => [
                'nullable',
                'string',
                'in:commande,system,promotion',
            ],
            'id_employes' => [
                'nullable',
                'array',
            ],
            'id_employes.*' => [
                'integer',
                'exists:utilisateur,id_utilisateur',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire',
            'titre.string' => 'Le titre doit être une chaîne de caractères',
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.string' => 'Le message doit être une chaîne de caractères',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 1000 caractères',
            'type_notification.in' => 'Le type de notification doit être : commande, system ou promotion',
            'id_employes.array' => 'Les identifiants des employés doivent être un tableau',
            'id_employes.*.integer' => 'Chaque identifiant d\'employé doit être un entier',
            'id_employes.*.exists' => 'Un ou plusieurs employés spécifiés n\'existent pas',
        ];
    }
}
