<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tag $tag): bool
    {
        if ($tag->user_id === null) {
            return false;
        }

        return $tag->user_id === $user->id;
    }

    public function delete(User $user, Tag $tag): bool
    {
        if ($tag->user_id === null) {
            return false;
        }

        return $tag->user_id === $user->id;
    }
}
