<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Debt extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'debt_client_id', 'user_id', 'original_amount', 'paid_amount',
        'balance', 'receipt_reference', 'description', 'debt_date', 'due_date', 'status',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'debt_date' => 'date',
        'due_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(DebtClient::class, 'debt_client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
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