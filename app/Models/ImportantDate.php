<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasEntityDefaults;
use Database\Factories\ImportantDateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $id
 * @property int $user_id
 * @property string $label
 * @property \Carbon\CarbonInterface $date
 * @property bool $recurs_annually
 * @property bool $is_done
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 * @property \Carbon\CarbonInterface|null $deleted_at
 */
class ImportantDate extends Model
{
    /** @use HasFactory<ImportantDateFactory> */
    use HasEntityDefaults, HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'date',
        'recurs_annually',
        'is_done',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'recurs_annually' => 'boolean',
            'is_done' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /** @return MorphMany<EntityPosition, $this> */
    public function positions(): MorphMany
    {
        return $this->morphMany(EntityPosition::class, 'entity');
    }

    /** @return MorphMany<EntityRelationship, $this> */
    public function relationshipsAsA(): MorphMany
    {
        return $this->morphMany(EntityRelationship::class, 'entity_a');
    }

    /** @return MorphMany<EntityRelationship, $this> */
    public function relationshipsAsB(): MorphMany
    {
        return $this->morphMany(EntityRelationship::class, 'entity_b');
    }
}
