<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnexionLog extends Model
{
    use HasFactory;

    protected $table = 'connexion_log';
    
    // Pas de updated_at, seulement created_at
    const UPDATED_AT = null;
    
    protected $fillable = [
        'utilisateur_id',
        'email',
        'ip_address',
        'user_agent',
        'statut',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id', 'id_utilisateur');
    }
}
