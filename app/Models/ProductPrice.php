<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_variant_id', 'price_type', 'price', 'min_quantity', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}