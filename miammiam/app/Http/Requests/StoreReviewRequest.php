<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent laisser un avis
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        return [
            'type_avis' => [
                'required',
                'string',
                Rule::in(['plat', 'service']),
            ],
            'note' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Si type_avis = 'plat', id_menuitem est obligatoire
            'id_menuitem' => [
                'required_if:type_avis,plat',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            // id_commande est optionnel - la vérification d'existence et de propriété se fait dans le controller
            'id_commande' => [
                'nullable',
                'integer',
                // Pas de 'exists' ici car on veut vérifier dans le controller que la commande appartient à l'utilisateur
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'type_avis.required' => 'Le type d\'avis est obligatoire',
            'type_avis.in' => 'Le type d\'avis doit être "plat" ou "service"',
            'note.required' => 'La note est obligatoire',
            'note.integer' => 'La note doit être un nombre entier',
            'note.min' => 'La note doit être au moins 1',
            'note.max' => 'La note ne peut pas dépasser 5',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères',
            'id_menuitem.required_if' => 'L\'identifiant du plat est obligatoire pour un avis sur un plat',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Ce plat n\'existe pas',
            'id_commande.integer' => 'L\'identifiant de la commande doit être un nombre entier',
            'id_commande.exists' => 'Cette commande n\'existe pas',
        ];
    }
}

                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Si type_avis = 'plat', id_menuitem est obligatoire
            'id_menuitem' => [
                'required_if:type_avis,plat',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            // id_commande est optionnel - la vérification d'existence et de propriété se fait dans le controller
            'id_commande' => [
                'nullable',
                'integer',
                // Pas de 'exists' ici car on veut vérifier dans le controller que la commande appartient à l'utilisateur
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'type_avis.required' => 'Le type d\'avis est obligatoire',
            'type_avis.in' => 'Le type d\'avis doit être "plat" ou "service"',
            'note.required' => 'La note est obligatoire',
            'note.integer' => 'La note doit être un nombre entier',
            'note.min' => 'La note doit être au moins 1',
            'note.max' => 'La note ne peut pas dépasser 5',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères',
            'id_menuitem.required_if' => 'L\'identifiant du plat est obligatoire pour un avis sur un plat',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Ce plat n\'existe pas',
            'id_commande.integer' => 'L\'identifiant de la commande doit être un nombre entier',
            'id_commande.exists' => 'Cette commande n\'existe pas',
        ];
    }
}

                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Si type_avis = 'plat', id_menuitem est obligatoire
            'id_menuitem' => [
                'required_if:type_avis,plat',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            // id_commande est optionnel - la vérification d'existence et de propriété se fait dans le controller
            'id_commande' => [
                'nullable',
                'integer',
                // Pas de 'exists' ici car on veut vérifier dans le controller que la commande appartient à l'utilisateur
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'type_avis.required' => 'Le type d\'avis est obligatoire',
            'type_avis.in' => 'Le type d\'avis doit être "plat" ou "service"',
            'note.required' => 'La note est obligatoire',
            'note.integer' => 'La note doit être un nombre entier',
            'note.min' => 'La note doit être au moins 1',
            'note.max' => 'La note ne peut pas dépasser 5',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères',
            'id_menuitem.required_if' => 'L\'identifiant du plat est obligatoire pour un avis sur un plat',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Ce plat n\'existe pas',
            'id_commande.integer' => 'L\'identifiant de la commande doit être un nombre entier',
            'id_commande.exists' => 'Cette commande n\'existe pas',
        ];
    }
}
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            // Si type_avis = 'plat', id_menuitem est obligatoire
            'id_menuitem' => [
                'required_if:type_avis,plat',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            // id_commande est optionnel - la vérification d'existence et de propriété se fait dans le controller
            'id_commande' => [
                'nullable',
                'integer',
                // Pas de 'exists' ici car on veut vérifier dans le controller que la commande appartient à l'utilisateur
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'type_avis.required' => 'Le type d\'avis est obligatoire',
            'type_avis.in' => 'Le type d\'avis doit être "plat" ou "service"',
            'note.required' => 'La note est obligatoire',
            'note.integer' => 'La note doit être un nombre entier',
            'note.min' => 'La note doit être au moins 1',
            'note.max' => 'La note ne peut pas dépasser 5',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères',
            'id_menuitem.required_if' => 'L\'identifiant du plat est obligatoire pour un avis sur un plat',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Ce plat n\'existe pas',
            'id_commande.integer' => 'L\'identifiant de la commande doit être un nombre entier',
            'id_commande.exists' => 'Cette commande n\'existe pas',
        ];
    }
}
