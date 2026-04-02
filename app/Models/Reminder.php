<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use App\Enums\ReminderType;
use App\Models\Concerns\HasEntityDefaults;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Reminder extends Model
{
    use HasEntityDefaults, HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'remind_at',
        'mood',
        'reminder_type',
        'is_completed',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'mood' => Mood::class,
            'reminder_type' => ReminderType::class,
            'is_completed' => 'boolean',
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

    public function isDue(): bool
    {
        return ! $this->is_completed
            && Carbon::parse((string) $this->remind_at)->startOfDay()->lte(now()->startOfDay());
    }
}
