<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model_name',
        'cost_price',
        'selling_price',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Relasi ke seluruh Kontrak yang mentransaksikan Produk ini (1:N).
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
