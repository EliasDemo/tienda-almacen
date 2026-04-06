<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleTicket extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'promotion_id', 'sale_id', 'customer_id',
        'ticket_code', 'is_winner',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}