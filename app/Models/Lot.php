<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'product_variant_id', 'lot_code', 'supplier',
        'purchase_price_per_kg', 'purchase_price_per_unit',
        'total_quantity', 'unit', 'remaining_quantity',
        'entry_date', 'expiry_date', 'notes',
    ];

    protected $casts = [
        'purchase_price_per_kg' => 'decimal:2',
        'purchase_price_per_unit' => 'decimal:2',
        'total_quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'entry_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function getDispatchedQuantityAttribute(): float
    {
        return round((float)$this->total_quantity - (float)$this->remaining_quantity, 3);
    }

    public function getDispatchedPercentAttribute(): float
    {
        if ($this->total_quantity <= 0) return 0;
        return round(($this->dispatched_quantity / $this->total_quantity) * 100, 1);
    }

    public function hasStock(): bool
    {
        return $this->remaining_quantity > 0;
    }
}