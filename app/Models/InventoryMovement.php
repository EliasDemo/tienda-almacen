<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'product_variant_id', 'package_id', 'location',
        'movement_type', 'quantity', 'unit',
        'reference_type', 'reference_id',
        'user_id', 'note', 'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}