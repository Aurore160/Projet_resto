<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favori extends Model
{
    use HasFactory;

    // 1. Spécifier le nom de la table (par défaut Laravel chercherait "favoris" au pluriel)
    protected $table = 'favoris';
    
    // 2. Spécifier la clé primaire
    protected $primaryKey = 'id_favori';
    
    // 3. Configurer les timestamps (notre table utilise 'date_ajout' au lieu de 'created_at')
    const CREATED_AT = 'date_ajout';
    const UPDATED_AT = null; // Pas de updated_at car on ne modifie pas un favori
    
    // 4. Définir les champs qu'on peut modifier (sécurité)
    protected $fillable = [
        'id_utilisateur',
        'id_menuitem',
        // Pas besoin d'id_favori (auto-généré)
        // Pas besoin de date_ajout (automatique)
    ];

    // 5. Définir les types de données (casting)
    protected $casts = [
        'id_utilisateur' => 'integer',
        'id_menuitem' => 'integer',
        'date_ajout' => 'datetime',
    ];

    // 6. Relation : un favori appartient à un utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    // 7. Relation : un favori appartient à un plat (menu_item)
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'id_menuitem', 'id_menuitem');
    }
}

