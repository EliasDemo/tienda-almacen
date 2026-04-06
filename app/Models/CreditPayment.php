<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $fillable = [
        'customer_credit_id', 'cash_register_id', 'user_id',
        'amount', 'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function credit(): BelongsTo
    {
        return $this->belongsTo(CustomerCredit::class, 'customer_credit_id');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}