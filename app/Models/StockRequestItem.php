<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequestItem extends Model
{
    protected $fillable = [
        'stock_request_id', 'product_variant_id',
        'quantity_requested', 'unit', 'package_type',
        'sale_price', 'quantity_sent', 'real_total', 'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'sale_price'         => 'decimal:2',
        'quantity_sent'      => 'decimal:3',
        'real_total'         => 'decimal:2',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Recalcular cantidad enviada y total real desde paquetes vinculados
     */
    public function recalculateFromPackages(): void
    {
        $packages = $this->packages()->whereIn('status', ['closed', 'opened', 'sold', 'exhausted'])->get();

        if ($this->unit === 'kg') {
            $this->quantity_sent = $packages->sum('gross_weight');
        } else {
            $this->quantity_sent = $packages->sum('unit_count');
        }

        $this->real_total = round((float) $this->quantity_sent * (float) $this->sale_price, 2);
        $this->save();
    }

    public function getSubtotalAttribute(): float
    {
        if ((float) $this->real_total > 0) {
            return (float) $this->real_total;
        }
        // Estimado solo para productos por unidad
        if ($this->unit === 'unit') {
            return round((float) $this->quantity_requested * (float) $this->sale_price, 2);
        }
        return 0;
    }

    public function getPackagesReceivedAttribute(): int
    {
        return $this->packages()->where('location', 'tienda')->count();
    }

    public function getIsFulfilledAttribute(): bool
    {
        return $this->packages()->count() >= (int) $this->quantity_requested;
    }
}