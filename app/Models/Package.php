<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Package extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'lot_id', 'transfer_line_id', 'package_type',
        'gross_weight', 'unit_count', 'net_weight', 'net_units',
        'status', 'location', 'validated_by',
        'received_at', 'opened_at', 'notes',  'stock_request_item_id', 'for_order',
    ];

    protected $casts = [
        'gross_weight' => 'decimal:3',
        'unit_count'   => 'integer',
        'net_weight'   => 'decimal:3',
        'net_units'    => 'integer',
        'received_at'  => 'datetime',
        'opened_at'    => 'datetime',
        'for_order'    => 'boolean',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function transferLine(): BelongsTo
    {
        return $this->belongsTo(TransferLine::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function isByWeight(): bool
    {
        return $this->gross_weight !== null;
    }

    public function getMermaKgAttribute(): float
    {
        return (float) $this->transferLine->merma_kg;
    }

    public function stockRequestItem(): BelongsTo
    {
        return $this->belongsTo(StockRequestItem::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        if ($this->status !== 'opened') {
            return 0;
        }

        $sold = $this->inventoryMovements()
            ->where('movement_type', 'SALE')
            ->sum('quantity');

        if ($this->isByWeight()) {
            return round((float) $this->net_weight + (float) $sold, 3);
        }

        return (int) $this->net_units + (int) $sold;
    }
}
