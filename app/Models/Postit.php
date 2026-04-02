<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use App\Models\Concerns\HasEntityDefaults;
use Database\Factories\PostitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Postit extends Model
{
    /** @use HasFactory<PostitFactory> */
    use HasEntityDefaults, HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function positions(): MorphMany
    {
        return $this->morphMany(EntityPosition::class, 'entity');
    }

    public function relationshipsAsA(): MorphMany
    {
        return $this->morphMany(EntityRelationship::class, 'entity_a');
    }

    public function relationshipsAsB(): MorphMany
    {
        return $this->morphMany(EntityRelationship::class, 'entity_b');
    }
}
