<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignOrder extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * La vérification du rôle (employé, gérant, admin) se fait via le middleware 'role'
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
            'id_livreur' => 'required|integer|exists:utilisateur,id_utilisateur',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}


    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_livreur.required' => 'L\'identifiant du livreur est obligatoire',
            'id_livreur.integer' => 'L\'identifiant du livreur doit être un nombre entier',
            'id_livreur.exists' => 'Le livreur spécifié n\'existe pas',
        ];
    }
}
