<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityShare;
use App\Models\User;

class EntitySharePolicy
{
    /**
     * Guest users cannot create entity shares (share with friends).
     */
    public function create(User $user): bool
    {
        return ! $user->isGuest();
    }

    /**
     * Guest users cannot delete entity shares.
     */
    public function delete(User $user, EntityShare $share): bool
    {
        return ! $user->isGuest() && $share->owner_id === $user->id;
    }
}
