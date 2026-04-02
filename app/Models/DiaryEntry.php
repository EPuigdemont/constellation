<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use App\Models\Concerns\HasEntityDefaults;
use Database\Factories\DiaryEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class DiaryEntry extends Model
{
    /** @use HasFactory<DiaryEntryFactory> */
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
