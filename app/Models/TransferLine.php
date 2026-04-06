<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferLine extends Model
{
    protected $fillable = [
        'transfer_id', 'product_variant_id', 'merma_kg',
        'total_packages', 'received_packages', 'transit_sold_packages', 'notes',
    ];

    protected $casts = [
        'merma_kg' => 'decimal:3',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function getTotalWeightAttribute(): float
    {
        return (float) $this->packages()->sum('gross_weight');
    }

    public function getTotalUnitsAttribute(): float
    {
        return (int) $this->packages()->sum('unit_count');
    }
}