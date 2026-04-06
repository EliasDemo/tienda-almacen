<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'name', 'phone', 'document', 'price_type',
        'discount_percent', 'notes', 'is_active',
        'credit_blocked', 'credit_limit', 'block_reason',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'credit_limit'     => 'decimal:2',
        'is_active'        => 'boolean',
        'credit_blocked'   => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(CustomerCredit::class);
    }

    public function getTotalCreditBalanceAttribute(): float
    {
        return (float) $this->credits()->where('status', '!=', 'paid')->sum('balance');
    }

    public function canReceiveCredit(): bool
    {
        if ($this->credit_blocked) {
            return false;
        }

        if ((float) $this->credit_limit <= 0) {
            return true;
        }

        return $this->total_credit_balance < (float) $this->credit_limit;
    }

    public function getCreditBlockReasonAttribute(): string
    {
        if ($this->credit_blocked) {
            return $this->attributes['block_reason'] ?? 'Bloqueado manualmente por el administrador.';
        }

        if ((float) $this->credit_limit > 0 && $this->total_credit_balance >= (float) $this->credit_limit) {
            return 'Deuda pendiente (S/ ' . number_format($this->total_credit_balance, 2) . ') alcanzó el límite de S/ ' . number_format($this->credit_limit, 2) . '.';
        }

        return '';
    }
}