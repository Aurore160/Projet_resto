<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payment';
    protected $primaryKey = 'id_payment';
    
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null; // Pas de updated_at
    
    protected $fillable = [
        'id_commande',
        'montant',
        'methode',
        'statut_payment',
        'date_payment',
        'transaction_ref',
        'operateur_mobile_money',
        'numero_transaction',
        'carte_last_digits',
        'carte_type',
        'frais_transaction',
    ];
    
    protected $casts = [
        'montant' => 'decimal:2',
        'frais_transaction' => 'decimal:2',
        'date_payment' => 'datetime',
        'date_creation' => 'datetime',
    ];
    
    // Relation avec la commande
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'id_commande', 'id_commande');
    }
}