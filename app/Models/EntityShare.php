<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityShare extends Model
{
    /** @var list<string> */
    protected $fillable = ['owner_id', 'friend_id', 'entity_id', 'entity_type'];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}

