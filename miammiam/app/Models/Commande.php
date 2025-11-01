<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $table = 'commandes';
    protected $primaryKey = 'id_commande';
    
    const CREATED_AT = 'date_commande';
    const UPDATED_AT = 'date_modification';
    
    protected $fillable = [
        'id_utilisateur',
        'id_livreur',
        'numero_commande',
        'statut',
        'type_commande',
        'heure_arrivee_prevue',
        'adresse_livraison',
        'montant_total',
        'points_utilises',
        'reduction_points',
        'frais_livraison',
        'commentaire',
        'instructions_speciales',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'reduction_points' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'points_utilises' => 'integer',
        'id_livreur' => 'integer',
        'date_commande' => 'datetime',
        'date_modification' => 'datetime',
        'heure_arrivee_prevue' => 'datetime',
    ];

    // Relation avec l'utilisateur (client)
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    // Relation avec le livreur (si assigné)
    public function livreur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_livreur', 'id_utilisateur');
    }

    // Relation avec les articles de la commande
    public function articles()
    {
        return $this->hasMany(CommandeArticle::class, 'id_commande', 'id_commande');
    }

    // Calculer le total des articles
    public function getTotal()
    {
        return $this->articles->sum(function ($article) {
            return $article->prix_unitaire * $article->quantite;
        });
    }

    // Compter le nombre total d'articles
    public function getTotalArticles()
    {
        return $this->articles->sum('quantite');
    }
}