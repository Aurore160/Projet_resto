<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les employés peuvent envoyer des messages aux gérants
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Autoriser uniquement les employés, gérants et admins
        return in_array($user->role, ['employe', 'gerant', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sujet' => [
                'required',
                'string',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'type_message' => [
                'nullable',
                'string',
                'in:signalement,question,urgence,retard,erreur,client_absent,autre',
            ],
            'priorite' => [
                'nullable',
                'string',
                'in:basse,normale,haute,urgente',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'sujet.required' => 'Le sujet est obligatoire',
            'sujet.string' => 'Le sujet doit être une chaîne de caractères',
            'sujet.max' => 'Le sujet ne peut pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.string' => 'Le message doit être une chaîne de caractères',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 2000 caractères',
            'type_message.in' => 'Le type de message doit être : signalement, question, urgence, retard, erreur, client_absent ou autre',
            'priorite.in' => 'La priorité doit être : basse, normale, haute ou urgente',
        ];
    }
}

    {
        return [
            'sujet' => [
                'required',
                'string',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'type_message' => [
                'nullable',
                'string',
                'in:signalement,question,urgence,retard,erreur,client_absent,autre',
            ],
            'priorite' => [
                'nullable',
                'string',
                'in:basse,normale,haute,urgente',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'sujet.required' => 'Le sujet est obligatoire',
            'sujet.string' => 'Le sujet doit être une chaîne de caractères',
            'sujet.max' => 'Le sujet ne peut pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.string' => 'Le message doit être une chaîne de caractères',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 2000 caractères',
            'type_message.in' => 'Le type de message doit être : signalement, question, urgence, retard, erreur, client_absent ou autre',
            'priorite.in' => 'La priorité doit être : basse, normale, haute ou urgente',
        ];
    }
}
