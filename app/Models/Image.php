<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'imageable_type', 'imageable_id', 'path', 'filename',
        'mime_type', 'size', 'type', 'sort_order', 'description',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}