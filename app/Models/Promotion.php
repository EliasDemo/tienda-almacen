<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'name', 'description', 'min_purchase',
        'tickets_per_purchase', 'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'min_purchase' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(RaffleTicket::class);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->start_date->lte(now())
            && $this->end_date->gte(now());
    }
}