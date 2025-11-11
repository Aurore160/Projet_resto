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
            'nom' => 'sometimes|nullable|string|max:100',
            'prenom' => 'sometimes|nullable|string|max:100',
            'postnom' => 'nullable|string|max:100',
            'email' => 'sometimes|nullable|email|max:255|unique:utilisateur,email,' . $userId . ',id_utilisateur',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'adresse_livraison' => 'nullable|string',
            'adresse_facturation' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
            
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
            'photo.image' => 'Le fichier doit être une image',
            'photo.mimes' => 'L\'image doit être au format JPEG, PNG, JPG, GIF ou WebP',
            'photo.max' => 'L\'image ne doit pas dépasser 5MB',
            'nouveau_mot_de_passe.regex' => 'Le nouveau mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial (@$!%*?&)',
            'nouveau_mot_de_passe.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas',
            'mot_de_passe_actuel.required_with' => 'Le mot de passe actuel est requis pour changer de mot de passe',
        ];
    }
}