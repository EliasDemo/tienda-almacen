<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'user_id', 'opening_amount', 'total_sales', 'total_cash',
        'total_other', 'closing_amount', 'difference', 'status',
        'opened_at', 'closed_at', 'notes',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_other' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}