<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'paid_at',
        'status',
        'wa_reminder_sent_at',
    ];

    protected $casts = [
        'due_date'            => 'date',
        'paid_at'             => 'datetime',
        'wa_reminder_sent_at' => 'datetime',
        'amount'              => 'decimal:2',
        'paid_amount'         => 'decimal:2',
    ];

    /**
     * Relasi balik ke Kontrak Utama (N:1).
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
