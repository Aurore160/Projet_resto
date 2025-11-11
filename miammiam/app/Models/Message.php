<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    
    protected $primaryKey = 'id_message';
    
    public $timestamps = false; // Utilise date_envoi au lieu de created_at
    
    protected $fillable = [
        'id_expediteur',
        'id_destinataire',
        'sujet',
        'message',
        'type_message',
        'priorite',
        'statut',
        'date_envoi',
        'date_lecture',
        'reponse',
        'date_reponse',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'date_lecture' => 'datetime',
        'date_reponse' => 'datetime',
    ];

    /**
     * Relation avec l'expéditeur (employé)
     */
    public function expediteur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_expediteur', 'id_utilisateur');
    }

    /**
     * Relation avec le destinataire (gérant)
     */
    public function destinataire()
    {
        return $this->belongsTo(Utilisateur::class, 'id_destinataire', 'id_utilisateur');
    }

    /**
     * Scope pour les messages envoyés
     */
    public function scopeSent($query)
    {
        return $query->where('statut', 'envoye');
    }

    /**
     * Scope pour les messages lus
     */
    public function scopeRead($query)
    {
        return $query->where('statut', 'lu');
    }

    /**
     * Scope pour les messages répondus
     */
    public function scopeReplied($query)
    {
        return $query->where('statut', 'repondu');
    }

    /**
     * Scope pour les messages résolus
     */
    public function scopeResolved($query)
    {
        return $query->where('statut', 'resolu');
    }

    /**
     * Scope pour un type de message spécifique
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type_message', $type);
    }

    /**
     * Scope pour une priorité spécifique
     */
    public function scopeOfPriority($query, $priorite)
    {
        return $query->where('priorite', $priorite);
    }

    /**
     * Marquer le message comme lu
     */
    public function markAsRead()
    {
        if ($this->statut === 'envoye') {
            $this->update([
                'statut' => 'lu',
                'date_lecture' => now(),
            ]);
        }
    }
}
