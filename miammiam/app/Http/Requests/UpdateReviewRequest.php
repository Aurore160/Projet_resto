<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Seuls les gérants et admins peuvent répondre aux avis
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        // Autoriser uniquement les gérants et admins
        return in_array($user->role, ['gerant', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}

    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
    {
        return [
            'reponse_gerant' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }
    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'reponse_gerant.required' => 'La réponse est obligatoire',
            'reponse_gerant.string' => 'La réponse doit être une chaîne de caractères',
            'reponse_gerant.min' => 'La réponse doit contenir au moins 10 caractères',
            'reponse_gerant.max' => 'La réponse ne peut pas dépasser 1000 caractères',
        ];
    }
}
