<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transfer extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'transfer_code', 'dispatched_by', 'received_by',
        'stock_request_id',
        'status', 'dispatched_at', 'received_at', 'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransferLine::class);
    }

    public function packages(): HasManyThrough
    {
        return $this->hasManyThrough(Package::class, TransferLine::class);
    }

    public function stockRequest(): HasOne
    {
        return $this->hasOne(StockRequest::class);
    }

    public function stockRequestOrder(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class, 'stock_request_id');
    }

    public function getTotalPackagesAttribute(): int
    {
        return $this->lines->sum('total_packages');
    }

    public function isForOrder(): bool
    {
        return $this->stock_request_id !== null;
    }
}