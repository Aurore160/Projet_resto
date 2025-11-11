<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeArticle extends Model
{
    use HasFactory;

    protected $table = 'commande_articles';
    protected $primaryKey = 'id_commande_article';
    
    const CREATED_AT = 'date_ajout';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'id_commande',
        'id_menuitem',
        'quantite',
        'prix_unitaire',
        'instructions',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'sous_total' => 'decimal:2',
        'date_ajout' => 'datetime',
    ];

    // Relation avec la commande (panier)
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }

    // Relation avec le plat
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'id_menuitem', 'id_menuitem');
    }

    // Calculer le sous-total (normalement calculé automatiquement en BDD)
    public function getSousTotal()
    {
        return $this->prix_unitaire * $this->quantite;
    }
}