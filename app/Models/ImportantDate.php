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
