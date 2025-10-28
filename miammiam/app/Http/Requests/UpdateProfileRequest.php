<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id_utilisateur;

        return [
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:255|unique:utilisateur,email,' . $userId . ',id_utilisateur',
            'telephone' => 'nullable|string|max:20',
            'adresse_livraison' => 'nullable|string',
            'adresse_facturation' => 'nullable|string',
            
            'mot_de_passe_actuel' => 'required_with:nouveau_mot_de_passe|string',
            'nouveau_mot_de_passe' => [
                'nullable',
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
            'email.unique' => 'Cet email est déjà utilisé par un autre compte',
            'nouveau_mot_de_passe.regex' => 'Le nouveau mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@$!%*?&)',
            'nouveau_mot_de_passe.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas',
            'mot_de_passe_actuel.required_with' => 'Le mot de passe actuel est requis pour changer de mot de passe',
        ];
    }
}
