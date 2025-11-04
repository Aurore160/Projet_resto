<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotion';
    
    protected $primaryKey = 'id_promo';
    
    public $timestamps = false; // Pas de created_at/updated_at
    
    protected $fillable = [
        'titre',
        'type_promotion',
        'valeur',
        'valeur_minimum_panier',
        'date_debut',
        'date_fin',
        'applicabilite',
        'details',
        'code_promo',
        'utilisations_max',
        'utilisations_actuelles',
        'statut',
        'image_url',
        'createur_id',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'valeur_minimum_panier' => 'decimal:2',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'utilisations_max' => 'integer',
        'utilisations_actuelles' => 'integer',
    ];

    /**
     * Relation : le créateur de la promotion
     */
    public function createur()
    {
        return $this->belongsTo(Utilisateur::class, 'createur_id', 'id_utilisateur');
    }

    /**
     * Relation : les plats en promotion
     */
    public function plats()
    {
        return $this->belongsToMany(MenuItem::class, 'promo_menu_item', 'id_promo', 'id_menuitem')
            ->withPivot('prix_promotionnel', 'date_debut', 'date_fin', 'statut')
            ->withTimestamps();
    }

    /**
     * Scope : promotions actives
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now());
    }

    /**
     * Scope : promotions disponibles (actives et non épuisées)
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->where(function ($q) {
                $q->whereNull('utilisations_max')
                  ->orWhereColumn('utilisations_actuelles', '<', 'utilisations_max');
            });
    }

    /**
     * Scope : promotions expirées
     */
    public function scopeExpirees($query)
    {
        return $query->where('date_fin', '<', now())
            ->orWhere('statut', 'expiree');
    }

    /**
     * Scope : promotions épuisées
     */
    public function scopeEpuisees($query)
    {
        return $query->where('statut', 'epuisee')
            ->orWhere(function ($q) {
                $q->whereNotNull('utilisations_max')
                  ->whereColumn('utilisations_actuelles', '>=', 'utilisations_max');
            });
    }

    /**
     * Vérifier si la promotion est encore valide
     */
    public function estValide(): bool
    {
        if ($this->statut !== 'active') {
            return false;
        }

        $now = now();
        if ($now->lt($this->date_debut) || $now->gt($this->date_fin)) {
            return false;
        }

        if ($this->utilisations_max !== null && $this->utilisations_actuelles >= $this->utilisations_max) {
            return false;
        }

        return true;
    }

    /**
     * Calculer la réduction pour un montant donné
     */
    public function calculerReduction($montant): float
    {
        if (!$this->estValide()) {
            return 0;
        }

        // Vérifier le minimum du panier
        if ($montant < $this->valeur_minimum_panier) {
            return 0;
        }

        switch ($this->type_promotion) {
            case 'pourcentage':
                return ($montant * $this->valeur) / 100;
            
            case 'montant_fixe':
                return min($this->valeur, $montant);
            
            default:
                return 0;
        }
    }
}

    protected $table = 'promotion';
    
    protected $primaryKey = 'id_promo';
    
    public $timestamps = false; // Pas de created_at/updated_at
    
    protected $fillable = [
        'titre',
        'type_promotion',
        'valeur',
        'valeur_minimum_panier',
        'date_debut',
        'date_fin',
        'applicabilite',
        'details',
        'code_promo',
        'utilisations_max',
        'utilisations_actuelles',
        'statut',
        'image_url',
        'createur_id',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'valeur_minimum_panier' => 'decimal:2',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'utilisations_max' => 'integer',
        'utilisations_actuelles' => 'integer',
    ];

    /**
     * Relation : le créateur de la promotion
     */
    public function createur()
    {
        return $this->belongsTo(Utilisateur::class, 'createur_id', 'id_utilisateur');
    }

    /**
     * Relation : les plats en promotion
     */
    public function plats()
    {
        return $this->belongsToMany(MenuItem::class, 'promo_menu_item', 'id_promo', 'id_menuitem')
            ->withPivot('prix_promotionnel', 'date_debut', 'date_fin', 'statut')
            ->withTimestamps();
    }

    /**
     * Scope : promotions actives
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now());
    }

    /**
     * Scope : promotions disponibles (actives et non épuisées)
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->where(function ($q) {
                $q->whereNull('utilisations_max')
                  ->orWhereColumn('utilisations_actuelles', '<', 'utilisations_max');
            });
    }

    /**
     * Scope : promotions expirées
     */
    public function scopeExpirees($query)
    {
        return $query->where('date_fin', '<', now())
            ->orWhere('statut', 'expiree');
    }

    /**
     * Scope : promotions épuisées
     */
    public function scopeEpuisees($query)
    {
        return $query->where('statut', 'epuisee')
            ->orWhere(function ($q) {
                $q->whereNotNull('utilisations_max')
                  ->whereColumn('utilisations_actuelles', '>=', 'utilisations_max');
            });
    }

    /**
     * Vérifier si la promotion est encore valide
     */
    public function estValide(): bool
    {
        if ($this->statut !== 'active') {
            return false;
        }

        $now = now();
        if ($now->lt($this->date_debut) || $now->gt($this->date_fin)) {
            return false;
        }

        if ($this->utilisations_max !== null && $this->utilisations_actuelles >= $this->utilisations_max) {
            return false;
        }

        return true;
    }

    /**
     * Calculer la réduction pour un montant donné
     */
    public function calculerReduction($montant): float
    {
        if (!$this->estValide()) {
            return 0;
        }

        // Vérifier le minimum du panier
        if ($montant < $this->valeur_minimum_panier) {
            return 0;
        }

        switch ($this->type_promotion) {
            case 'pourcentage':
                return ($montant * $this->valeur) / 100;
            
            case 'montant_fixe':
                return min($this->valeur, $montant);
            
            default:
                return 0;
        }
    }
}
