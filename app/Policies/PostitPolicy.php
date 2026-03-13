<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Postit;
use App\Models\User;

class PostitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Postit $postit): bool
    {
        return $postit->user_id === $user->id || $postit->is_public;
    }

    public function create(User $user): bool
    {
        return true;
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
