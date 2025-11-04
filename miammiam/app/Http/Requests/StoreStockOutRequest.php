<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les employés peuvent signaler une rupture de stock
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
            'id_menuitem' => [
                'required',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_menuitem.required' => 'L\'identifiant du plat est obligatoire',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Le plat spécifié n\'existe pas',
            'commentaire.string' => 'Le commentaire doit être une chaîne de caractères',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères',
        ];
    }
}


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les employés peuvent signaler une rupture de stock
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
            'id_menuitem' => [
                'required',
                'integer',
                'exists:menu_item,id_menuitem',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_menuitem.required' => 'L\'identifiant du plat est obligatoire',
            'id_menuitem.integer' => 'L\'identifiant du plat doit être un nombre entier',
            'id_menuitem.exists' => 'Le plat spécifié n\'existe pas',
            'commentaire.string' => 'Le commentaire doit être une chaîne de caractères',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 500 caractères',
        ];
    }
}

