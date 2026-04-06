<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestPayment extends Model
{
    protected $fillable = [
        'stock_request_id', 'cash_register_id', 'user_id',
        'amount', 'method', 'payment_type', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(StockRequest::class);
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
