<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    /**
     * Guest users cannot create friendships (send friend requests).
     */
    public function create(User $user): bool
    {
        // Guest users cannot add friends
        return ! $user->isGuest();
    }

    /**
     * Guest users cannot manage friendships.
     */
    public function update(User $user, Friendship $friendship): bool
    {
        return ! $user->isGuest();
    }

    /**
     * Guest users cannot delete friendships.
     */
    public function delete(User $user, Friendship $friendship): bool
    {
        return ! $user->isGuest();
    }
}
