<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Mood;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarDayMood extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'date',
        'mood',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
