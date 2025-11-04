<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $table = 'menu_item';
    protected $primaryKey = 'id_menuitem';
    
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_modification';
    
    protected $fillable = [
        'id_categorie',
        'nom',
        'description',
        'prix',
        'statut_disponibilite',
        'photo_url',
        'plat_du_jour',
        'temps_preparation',
        'ingredients',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'statut_disponibilite' => 'boolean',
        'plat_du_jour' => 'boolean',
        'temps_preparation' => 'integer',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime',
    ];

    // Relation avec la table categories
    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'id_categorie', 'id_categorie');
    }

    /**
     * Relation : les promotions appliquées à ce plat
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promo_menu_item', 'id_menuitem', 'id_promo')
            ->withPivot('prix_promotionnel', 'date_debut', 'date_fin', 'statut')
            ->withTimestamps();
    }

    /**
     * Récupérer le prix promotionnel actuel si une promotion est active
     */
    public function getPrixPromotionnel()
    {
        $promoActive = PromoMenuItem::where('id_menuitem', $this->id_menuitem)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            })
            ->first();

        return $promoActive ? $promoActive->prix_promotionnel : null;
    }

    /**
     * Vérifier si le plat est en promotion
     */
    public function estEnPromotion(): bool
    {
        return $this->getPrixPromotionnel() !== null;
    }
}

     * Relation : les promotions appliquées à ce plat
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promo_menu_item', 'id_menuitem', 'id_promo')
            ->withPivot('prix_promotionnel', 'date_debut', 'date_fin', 'statut')
            ->withTimestamps();
    }

    /**
     * Récupérer le prix promotionnel actuel si une promotion est active
     */
    public function getPrixPromotionnel()
    {
        $promoActive = PromoMenuItem::where('id_menuitem', $this->id_menuitem)
            ->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            })
            ->first();

        return $promoActive ? $promoActive->prix_promotionnel : null;
    }

    /**
     * Vérifier si le plat est en promotion
     */
    public function estEnPromotion(): bool
    {
        return $this->getPrixPromotionnel() !== null;
    }
}
