<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $id
 * @property string $name
 * @property int|null $user_id
 * @property string|null $color
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'user_id',
        'color',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphToMany<DiaryEntry, $this> */
    public function diaryEntries(): MorphToMany
    {
        return $this->morphedByMany(DiaryEntry::class, 'taggable');
    }

    /** @return MorphToMany<Note, $this> */
    public function notes(): MorphToMany
    {
        return $this->morphedByMany(Note::class, 'taggable');
    }

    /** @return MorphToMany<Postit, $this> */
    public function postits(): MorphToMany
    {
        return $this->morphedByMany(Postit::class, 'taggable');
    }

    /** @return MorphToMany<Image, $this> */
    public function images(): MorphToMany
    {
        return $this->morphedByMany(Image::class, 'taggable');
    }

    /** @return MorphToMany<ImportantDate, $this> */
    public function importantDates(): MorphToMany
    {
        return $this->morphedByMany(ImportantDate::class, 'taggable');
    }

    /** @param Builder<Tag> $query
     * @return Builder<Tag>
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    /** @param Builder<Tag> $query
     * @return Builder<Tag>
     */
    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId)->orWhereNull('user_id');
    }
}
