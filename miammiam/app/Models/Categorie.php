<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'id_categorie';
    public $timestamps = false;
    
    protected $fillable = [
        'nom',
        'description',
    ];

    // Relation avec les plats
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'id_categorie', 'id_categorie');
    }
}
