<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:utilisateur,email',
            'mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
            ],
            'telephone' => 'nullable|string|max:20',
            'adresse_livraison' => 'nullable|string',
            'adresse_facturation' => 'nullable|string',
            'role' => 'required|in:etudiant,employe,gerant,admin',
        ];

        // Si le rôle est 'employe', ajouter les règles pour les champs employe
        if ($this->input('role') === 'employe') {
            $rules['matricule'] = 'required|string|max:20|unique:employe,matricule';
            $rules['role_specifique'] = 'required|in:cuisinier,serveur,livreur,caissier,manager';
            $rules['date_embauche'] = 'required|date';
            $rules['salaire'] = 'nullable|numeric|min:0';
            $rules['statut'] = 'nullable|in:actif,inactif,congé,licencie';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial',
            'role.required' => 'Le rôle est obligatoire',
            'role.in' => 'Le rôle doit être : etudiant, employe, gerant ou admin',
            // Messages pour les champs employe
            'matricule.required' => 'Le matricule est obligatoire pour un employé',
            'matricule.unique' => 'Ce matricule est déjà utilisé',
            'role_specifique.required' => 'Le rôle spécifique est obligatoire pour un employé',
            'role_specifique.in' => 'Le rôle spécifique doit être : cuisinier, serveur, livreur, caissier ou manager',
            'date_embauche.required' => 'La date d\'embauche est obligatoire pour un employé',
            'date_embauche.date' => 'La date d\'embauche doit être une date valide',
            'salaire.numeric' => 'Le salaire doit être un nombre',
            'salaire.min' => 'Le salaire ne peut pas être négatif',
            'statut.in' => 'Le statut doit être : actif, inactif, congé ou licencie',
        ];
    }
}

            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial',
            'role.required' => 'Le rôle est obligatoire',
            'role.in' => 'Le rôle doit être : etudiant, employe, gerant ou admin',
            // Messages pour les champs employe
            'matricule.required' => 'Le matricule est obligatoire pour un employé',
            'matricule.unique' => 'Ce matricule est déjà utilisé',
            'role_specifique.required' => 'Le rôle spécifique est obligatoire pour un employé',
            'role_specifique.in' => 'Le rôle spécifique doit être : cuisinier, serveur, livreur, caissier ou manager',
            'date_embauche.required' => 'La date d\'embauche est obligatoire pour un employé',
            'date_embauche.date' => 'La date d\'embauche doit être une date valide',
            'salaire.numeric' => 'Le salaire doit être un nombre',
            'salaire.min' => 'Le salaire ne peut pas être négatif',
            'statut.in' => 'Le statut doit être : actif, inactif, congé ou licencie',
        ];
    }
}
