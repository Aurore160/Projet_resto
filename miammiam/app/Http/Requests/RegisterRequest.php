<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:utilisateur,email',
            'mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
            'telephone' => 'nullable|string|max:20',
            'adresse_livraison' => 'nullable|string',
            'adresse_facturation' => 'nullable|string',
            'consentement_cookies' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@$!%*?&)',
            'email.unique' => 'Cet email est déjà utilisé',
        ];
    }
}
