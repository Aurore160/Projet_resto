<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    use HasFactory;

    protected $table = 'reclamation';
    
    protected $primaryKey = 'id_reclamation';
    
    public $timestamps = false; // Utilise date_reclamation au lieu de created_at
    
    protected $fillable = [
        'id_utilisateur',
        'id_commande',
        'id_employe_traitant',
        'sujet',
        'description',
        'type_reclamation',
        'priorite',
        'statut_reclamation',
        'reponse_employe',
        'date_reclamation',
        'date_modification',
        'date_fermeture',
        'satisfaction_client',
    ];

    protected $casts = [
        'date_reclamation' => 'datetime',
        'date_modification' => 'datetime',
        'date_fermeture' => 'datetime',
        'satisfaction_client' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur (nullable)
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Relation avec la commande (nullable)
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }

    /**
     * Relation avec l'employe traitant (nullable)
     */
    public function employeTraitant()
    {
        return $this->belongsTo(Utilisateur::class, 'id_employe_traitant', 'id_utilisateur');
    }

    /**
     * Scope pour les réclamations ouvertes
     */
    public function scopePending($query)
    {
        return $query->where('statut_reclamation', 'ouverte');
    }

    /**
     * Scope pour les réclamations résolues
     */
    public function scopeResolved($query)
    {
        return $query->where('statut_reclamation', 'resolue');
    }

    /**
     * Scope pour un statut spécifique
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('statut_reclamation', $status);
    }
}


    /**
     * Scope pour un statut spécifique
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('statut_reclamation', $status);
    }
}
