<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    use HasFactory;

    protected $table = 'avis';
    protected $primaryKey = 'id_avis';
    
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null; // Pas de mise à jour pour les avis
    
    protected $fillable = [
        'id_utilisateur',
        'id_menuitem',
        'id_commande',
        'type_avis',
        'note',
        'commentaire',
        'reponse_gerant',
        'date_reponse',
        'statut_moderation',
    ];

    protected $casts = [
        'id_utilisateur' => 'integer',
        'id_menuitem' => 'integer',
        'id_commande' => 'integer',
        'note' => 'integer',
        'date_creation' => 'datetime',
        'date_reponse' => 'datetime',
    ];

    /**
     * Relation : un avis appartient à un utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Relation : un avis peut être lié à un plat (nullable)
     */
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'id_menuitem', 'id_menuitem');
    }

    /**
     * Relation : un avis peut être lié à une commande (nullable)
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }

    /**
     * Scope : avis approuvés
     */
    public function scopeApprouves($query)
    {
        return $query->where('statut_moderation', 'approuve');
    }

    /**
     * Scope : avis en attente de modération
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut_moderation', 'en_attente');
    }

    /**
     * Scope : avis rejetés
     */
    public function scopeRejetes($query)
    {
        return $query->where('statut_moderation', 'rejete');
    }

    /**
     * Scope : avis avec réponse du gérant
     */
    public function scopeAvecReponse($query)
    {
        return $query->whereNotNull('reponse_gerant');
    }

    /**
     * Scope : avis sans réponse du gérant
     */
    public function scopeSansReponse($query)
    {
        return $query->whereNull('reponse_gerant');
    }

    /**
     * Scope : filtrer par type d'avis
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type_avis', $type);
    }

    /**
     * Scope : filtrer par note minimale
     */
    public function scopeMinNote($query, $note)
    {
        return $query->where('note', '>=', $note);
    }

    /**
     * Scope : filtrer par note maximale
     */
    public function scopeMaxNote($query, $note)
    {
        return $query->where('note', '<=', $note);
    }
}
