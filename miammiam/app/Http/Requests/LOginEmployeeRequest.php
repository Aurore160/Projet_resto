<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginEmployeeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true; // La connexion est publique
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'mot_de_passe' => 'required|string',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email doit être valide',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
        ];
    }

    /**
     * Gérer les erreurs de validation.
     */
    protected function failedValidation(Validator $validator)
    {
        \Log::error('Validation échouée pour login employé', [
            'errors' => $validator->errors()->all(),
            'input' => $this->all(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email doit être valide',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
        ];
    }

    /**
     * Gérer les erreurs de validation.
     */
    protected function failedValidation(Validator $validator)
    {
        \Log::error('Validation échouée pour login employé', [
            'errors' => $validator->errors()->all(),
            'input' => $this->all(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
