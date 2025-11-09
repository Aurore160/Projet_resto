<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    use HasFactory;

    protected $table = 'parrainages';
    
    protected $primaryKey = 'id_parrainage';
    
    public $timestamps = false; // Utilise date_parrainage au lieu de created_at
    
    protected $fillable = [
        'id_parrain',
        'id_filleul',
        'code_parrainage_utilise',
        'date_parrainage',
        'premiere_commande_faite',
        'date_premiere_commande',
        'points_inscription',
        'points_premiere_commande',
    ];

    protected $casts = [
        'date_parrainage' => 'datetime',
        'date_premiere_commande' => 'datetime',
        'premiere_commande_faite' => 'boolean',
        'points_inscription' => 'integer',
        'points_premiere_commande' => 'integer',
    ];

    /**
     * Relation : un parrainage appartient à un parrain (utilisateur qui a parrainé)
     */
    public function parrain()
    {
        return $this->belongsTo(Utilisateur::class, 'id_parrain', 'id_utilisateur');
    }

    /**
     * Relation : un parrainage appartient à un filleul (utilisateur parrainé)
     */
    public function filleul()
    {
        return $this->belongsTo(Utilisateur::class, 'id_filleul', 'id_utilisateur');
    }

    /**
     * Scope : parrainages où la première commande a été faite
     */
    public function scopePremiereCommandeFaite($query)
    {
        return $query->where('premiere_commande_faite', true);
    }

    /**
     * Scope : parrainages où la première commande n'a pas encore été faite
     */
    public function scopePremiereCommandeEnAttente($query)
    {
        return $query->where('premiere_commande_faite', false);
    }

    /**
     * Scope : parrainages d'un parrain spécifique
     */
    public function scopeByParrain($query, $idParrain)
    {
        return $query->where('id_parrain', $idParrain);
    }

    /**
     * Scope : parrainage d'un filleul spécifique
     */
    public function scopeByFilleul($query, $idFilleul)
    {
        return $query->where('id_filleul', $idFilleul);
    }
}
