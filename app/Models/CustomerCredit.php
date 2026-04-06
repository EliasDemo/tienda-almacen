<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerCredit extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'customer_id', 'sale_id', 'user_id',
        'original_amount', 'paid_amount', 'balance', 'status', 'notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function recalculateBalance(): void
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->balance = $this->original_amount - $this->paid_amount;
        $this->status = match (true) {
            $this->balance <= 0 => 'paid',
            $this->paid_amount > 0 => 'partial',
            default => 'pending',
        };
        $this->save();
    }
}