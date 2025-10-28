<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateur';
    protected $primaryKey = 'id_utilisateur';
    public $timestamps = false;
    const CREATED_AT = 'date_inscription';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'telephone',
        'adresse_livraison',
        'adresse_facturation',
        'role',
        'points_balance',
        'code_parrainage',
        'parrain_id',
        'statut_compte',
        'consentement_cookies',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    protected $casts = [
        'date_inscription' => 'datetime',
        'consentement_cookies' => 'boolean',
        'points_balance' => 'integer',
    ];

    public function parrain()
    {
        return $this->belongsTo(Utilisateur::class, 'parrain_id', 'id_utilisateur');
    }

    public function filleuls()
    {
        return $this->hasMany(Utilisateur::class, 'parrain_id', 'id_utilisateur');
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
}
