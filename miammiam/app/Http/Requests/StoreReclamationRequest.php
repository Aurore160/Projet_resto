<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReclamationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Autoriser même les utilisateurs non connectés à envoyer une réclamation
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
            'nom' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'telephone' => [
                'nullable',
                'string',
                'max:50',
            ],
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
            'id_commande' => [
                'nullable',
                'integer',
                // Pas de 'exists' ici car on veut vérifier dans le controller que la commande appartient à l'utilisateur (si connecté)
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'nom.string' => 'Le nom doit être une chaîne de caractères',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères',
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email doit être valide',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères',
            'telephone.max' => 'Le téléphone ne peut pas dépasser 50 caractères',
            'sujet.required' => 'Le sujet est obligatoire',
            'sujet.string' => 'Le sujet doit être une chaîne de caractères',
            'sujet.max' => 'Le sujet ne peut pas dépasser 255 caractères',
            'message.required' => 'Le message est obligatoire',
            'message.string' => 'Le message doit être une chaîne de caractères',
            'message.min' => 'Le message doit contenir au moins 10 caractères',
            'message.max' => 'Le message ne peut pas dépasser 2000 caractères',
            'id_commande.integer' => 'L\'identifiant de la commande doit être un nombre entier',
        ];
    }
}
