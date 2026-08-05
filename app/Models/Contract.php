<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'customer_id',
        'product_id',
        'down_payment',
        'total_price',
        'tenor_months',
        'monthly_installment',
        'start_date',
        'status',
    ];

    protected $casts = [
        'down_payment'        => 'decimal:2',
        'total_price'         => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'start_date'          => 'date',
    ];

    /**
     * Relasi balik ke Customer (N:1).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi balik ke Product HP (N:1).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke seluruh Jadwal Pembayaran Angsuran di dalam Kontrak ini (1:N).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
