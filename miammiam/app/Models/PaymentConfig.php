<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentConfig extends Model
{
    use HasFactory;

    protected $table = 'payment_configs';
    protected $primaryKey = 'id_payment_config';

    protected $fillable = [
        'provider',
        'mode',
        'cid_encrypted',
        'publishable_key_encrypted',
        'active',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Accesseur pour obtenir le CID déchiffré
     */
    public function getCidAttribute()
    {
        if (!$this->cid_encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($this->cid_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Accesseur pour obtenir la clé publique déchiffrée
     */
    public function getPublishableKeyAttribute()
    {
        if (!$this->publishable_key_encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($this->publishable_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mutateur pour chiffrer le CID avant stockage
     */
    public function setCidAttribute($value)
    {
        if ($value) {
            $this->attributes['cid_encrypted'] = Crypt::encryptString($value);
        }
    }

    /**
     * Mutateur pour chiffrer la clé publique avant stockage
     */
    public function setPublishableKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['publishable_key_encrypted'] = Crypt::encryptString($value);
        }
    }

    /**
     * Masquer partiellement une clé pour l'affichage sécurisé
     */
    public static function maskKey($key, $visibleStart = 4, $visibleEnd = 4)
    {
        if (!$key || strlen($key) < ($visibleStart + $visibleEnd)) {
            return str_repeat('*', 12);
        }
        
        $start = substr($key, 0, $visibleStart);
        $end = substr($key, -$visibleEnd);
        $middle = str_repeat('*', max(8, strlen($key) - $visibleStart - $visibleEnd));
        
        return $start . $middle . $end;
    }
}
