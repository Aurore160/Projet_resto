<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoMenuItem extends Model
{
    use HasFactory;

    protected $table = 'promo_menu_item';
    
    protected $primaryKey = 'id_promomenuitem';
    
    public $timestamps = false; // Pas de created_at/updated_at
    
    protected $fillable = [
        'id_promo',
        'id_menuitem',
        'prix_promotionnel',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'prix_promotionnel' => 'decimal:2',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    /**
     * Relation : la promotion
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'id_promo', 'id_promo');
    }

    /**
     * Relation : le plat du menu
     */
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'id_menuitem', 'id_menuitem');
    }

    /**
     * Scope : promotions actives pour les plats
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            });
    }

    /**
     * Vérifier si la promotion du plat est valide
     */
    public function estValide(): bool
    {
        if ($this->statut !== 'active') {
            return false;
        }

        $now = now();
        if ($this->date_debut && $now->lt($this->date_debut)) {
            return false;
        }

        if ($this->date_fin && $now->gt($this->date_fin)) {
            return false;
        }

        return true;
    }
}

    
    protected $primaryKey = 'id_promomenuitem';
    
    public $timestamps = false; // Pas de created_at/updated_at
    
    protected $fillable = [
        'id_promo',
        'id_menuitem',
        'prix_promotionnel',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'prix_promotionnel' => 'decimal:2',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    /**
     * Relation : la promotion
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'id_promo', 'id_promo');
    }

    /**
     * Relation : le plat du menu
     */
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'id_menuitem', 'id_menuitem');
    }

    /**
     * Scope : promotions actives pour les plats
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active')
            ->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>=', now());
            });
    }

    /**
     * Vérifier si la promotion du plat est valide
     */
    public function estValide(): bool
    {
        if ($this->statut !== 'active') {
            return false;
        }

        $now = now();
        if ($this->date_debut && $now->lt($this->date_debut)) {
            return false;
        }

        if ($this->date_fin && $now->gt($this->date_fin)) {
            return false;
        }

        return true;
    }
}
