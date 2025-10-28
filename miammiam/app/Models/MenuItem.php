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
}
