<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email|exists:utilisateur,email',
            'mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Le token est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.exists' => 'Aucun compte associé à cet email',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
            'mot_de_passe.confirmed' => 'Les mots de passe ne correspondent pas',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@$!%*?&)',
        ];
    }
}
