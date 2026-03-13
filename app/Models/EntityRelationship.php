<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RelationshipDirection;
use App\Enums\RelationshipType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EntityRelationship extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_a_id',
        'entity_a_type',
        'entity_b_id',
        'entity_b_type',
        'relationship_type',
        'direction',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => RelationshipType::class,
            'direction' => RelationshipDirection::class,
        ];
    }

    public function entityA(): MorphTo
    {
        return $this->morphTo('entity_a');
    }

    public function entityB(): MorphTo
    {
        return $this->morphTo('entity_b');
    }
}
