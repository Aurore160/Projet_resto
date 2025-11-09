<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
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
            'statut' => 'required|in:en_attente,confirmee,en_preparation,pret,livree,annulee',
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire',
            'statut.in' => 'Le statut doit être l\'un des suivants : en_attente, confirmee, en_preparation, pret, livree, annulee',
        ];
    }
}