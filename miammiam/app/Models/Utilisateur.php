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
        'photo',
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

    /**
     * Relation : le parrain de cet utilisateur (si parrainé)
     */
    public function parrain()
    {
        return $this->belongsTo(Utilisateur::class, 'parrain_id', 'id_utilisateur');
    }

    /**
     * Relation : les parrainages où cet utilisateur est le parrain
     */
    public function parrainages()
    {
        return $this->hasMany(Parrainage::class, 'id_parrain', 'id_utilisateur');
    }

    /**
     * Relation : les filleuls de cet utilisateur (les utilisateurs qu'il a parrainés)
     */
    public function filleuls()
    {
        return $this->hasManyThrough(
            Utilisateur::class,
            Parrainage::class,
            'id_parrain', // Clé étrangère dans parrainages
            'id_utilisateur', // Clé étrangère dans utilisateur
            'id_utilisateur', // Clé locale dans utilisateur (parrain)
            'id_filleul' // Clé locale dans parrainages
        );
    }

    /**
     * Relation : le parrainage de cet utilisateur (s'il est un filleul)
     */
    public function parrainage()
    {
        return $this->hasOne(Parrainage::class, 'id_filleul', 'id_utilisateur');
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
}


    /**
     * Relation : les filleuls de cet utilisateur (les utilisateurs qu'il a parrainés)
     */
    public function filleuls()
    {
        return $this->hasManyThrough(
            Utilisateur::class,
            Parrainage::class,
            'id_parrain', // Clé étrangère dans parrainages
            'id_utilisateur', // Clé étrangère dans utilisateur
            'id_utilisateur', // Clé locale dans utilisateur (parrain)
            'id_filleul' // Clé locale dans parrainages
        );
    }

    /**
     * Relation : le parrainage de cet utilisateur (s'il est un filleul)
     */
    public function parrainage()
    {
        return $this->hasOne(Parrainage::class, 'id_filleul', 'id_utilisateur');
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
}
