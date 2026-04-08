<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $entity_id
 * @property string $entity_type
 * @property string $context
 * @property float $x
 * @property float $y
 * @property int $z_index
 * @property float|null $width
 * @property float|null $height
 * @property bool $is_hidden
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
