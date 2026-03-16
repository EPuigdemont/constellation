<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityPosition extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'entity_id',
        'entity_type',
        'context',
        'x',
        'y',
        'z_index',
        'width',
        'height',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'z_index' => 'integer',
            'width' => 'float',
            'height' => 'float',
            'is_hidden' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
