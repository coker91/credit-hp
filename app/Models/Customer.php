<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    protected $fillable = [
        'nik',
        'name',
        'phone_number',
        'address',
    ];

    /**
     * Relasi ke seluruh Kontrak Cicilan milik Customer (1:N).
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Relasi tidak langsung ke seluruh Pembayaran Tagihan milik Customer via Contract (1:N melalui Contract).
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Contract::class);
    }
}
