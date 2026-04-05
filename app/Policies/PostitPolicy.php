<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityShare;
use App\Models\Postit;
use App\Models\User;
use App\Services\LimitCheckerService;

class PostitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Postit $postit): bool
    {
        // Owner can always view
        if ($postit->user_id === $user->id) {
            return true;
        }

        // Check if entity is shared with this user
        return EntityShare::query()
            ->where('owner_id', $postit->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $postit->id)
            ->where('entity_type', 'postit')
            ->exists();
    }

    public function create(User $user): bool
    {
        $limitChecker = app(LimitCheckerService::class);

        return $limitChecker->canCreateEntity($user, 'postit');
    }

    public function update(User $user, Postit $postit): bool
    {
        return $postit->user_id === $user->id;
    }

    public function delete(User $user, Postit $postit): bool
    {
        return $postit->user_id === $user->id;
    }

    public function restore(User $user, Postit $postit): bool
    {
        return $postit->user_id === $user->id;
    }

    public function forceDelete(User $user, Postit $postit): bool
    {
        return $postit->user_id === $user->id;
    }
}
