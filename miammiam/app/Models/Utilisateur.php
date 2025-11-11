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
        'date_inscription',
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

    /**
     * Accessor pour formater l'URL de la photo avec le bon port
     */
    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Si c'est déjà une URL complète commençant par http://localhost ou http://127.0.0.1 sans port
        if (preg_match('#^http://(localhost|127\.0\.0\.1)(/|/storage/)#', $value) && strpos($value, ':8000') === false) {
            // Remplacer http://localhost par http://localhost:8000
            $value = preg_replace('#^http://localhost#', 'http://localhost:8000', $value);
            $value = preg_replace('#^http://127\.0\.0\.1#', 'http://127.0.0.1:8000', $value);
            return $value;
        }

        // Si c'est déjà une URL complète avec https ou http avec port, la retourner telle quelle
        if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
            return $value;
        }

        // Si c'est un chemin relatif (commence par /storage/ ou storage/)
        if (strpos($value, '/storage/') === 0 || strpos($value, 'storage/') === 0) {
            $baseUrl = config('app.url', 'http://localhost:8000');
            // S'assurer que le port 8000 est présent pour localhost
            if (preg_match('#^http://localhost#', $baseUrl) && strpos($baseUrl, ':8000') === false) {
                $baseUrl = str_replace('http://localhost', 'http://localhost:8000', $baseUrl);
            } elseif (preg_match('#^http://127\.0\.0\.1#', $baseUrl) && strpos($baseUrl, ':8000') === false) {
                $baseUrl = str_replace('http://127.0.0.1', 'http://127.0.0.1:8000', $baseUrl);
            }
            // Normaliser le chemin
            $path = strpos($value, '/') === 0 ? $value : '/' . $value;
            return rtrim($baseUrl, '/') . $path;
        }

        // Si c'est juste un nom de fichier (profiles/xxx.jpg)
        if (strpos($value, 'profiles/') === 0 || strpos($value, '/profiles/') === 0) {
            $baseUrl = config('app.url', 'http://localhost:8000');
            // S'assurer que le port 8000 est présent pour localhost
            if (preg_match('#^http://localhost#', $baseUrl) && strpos($baseUrl, ':8000') === false) {
                $baseUrl = str_replace('http://localhost', 'http://localhost:8000', $baseUrl);
            } elseif (preg_match('#^http://127\.0\.0\.1#', $baseUrl) && strpos($baseUrl, ':8000') === false) {
                $baseUrl = str_replace('http://127.0.0.1', 'http://127.0.0.1:8000', $baseUrl);
            }
            // Normaliser le chemin
            $path = strpos($value, '/') === 0 ? $value : '/storage/' . $value;
            return rtrim($baseUrl, '/') . $path;
        }

        return $value;
    }
}
