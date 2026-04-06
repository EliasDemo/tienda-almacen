<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DebtClient extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'name', 'phone', 'document', 'notes', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->debts()->where('status', '!=', 'cancelled')->sum('balance');
    }
}