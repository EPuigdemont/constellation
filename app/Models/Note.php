<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use App\Models\Concerns\HasEntityDefaults;
use Carbon\CarbonInterface;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string $body
 * @property Mood|null $mood
 * @property string|null $color_override
 * @property bool $is_public
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasEntityDefaults, HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'mood',
        'color_override',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'mood' => Mood::class,
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
