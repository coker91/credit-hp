<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guarantor extends Model
{
    protected $fillable = [
        'customer_id',
        'nik',
        'name',
        'phone_number',
        'address',
        'relationship',
    ];

    /**
     * Relasi balik ke Customer yang dijamin (N:1).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
