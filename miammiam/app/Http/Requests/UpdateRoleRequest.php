<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:etudiant,employe,gerant,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Le rôle est obligatoire',
            'role.in' => 'Le rôle doit être : etudiant, employe, gerant ou admin',
        ];
    }
}
