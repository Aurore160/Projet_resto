<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    
    protected $primaryKey = 'id_notification';
    
    public $timestamps = false; // Utilise date_creation au lieu de created_at
    
    protected $fillable = [
        'id_utilisateur',
        'id_commande',
        'type_notification',
        'titre',
        'message',
        'lu',
        'date_creation',
        'date_lecture',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'date_creation' => 'datetime',
        'date_lecture' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Relation avec la commande (nullable)
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }

    /**
     * Marquer la notification comme lue
     */
    public function markAsRead()
    {
        if (!$this->lu) {
            $this->update([
                'lu' => true,
                'date_lecture' => now(),
            ]);
        }
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('lu', false);
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeRead($query)
    {
        return $query->where('lu', true);
    }

    /**
     * Scope pour un type de notification spécifique
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type_notification', $type);
    }
}

