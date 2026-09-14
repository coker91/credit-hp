<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'cost_price',
        'selling_price',
        'actual_cost_price',
        'default_tenor',
        'installment_reference',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'actual_cost_price' => 'decimal:2',
        'installment_reference' => 'decimal:2',
        'default_tenor' => 'integer',
    ];

    /**
     * Relasi ke seluruh Kontrak yang mentransaksikan Produk ini (1:N).
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
