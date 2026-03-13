<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Image;
use App\Models\User;

class ImagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Image $image): bool
    {
        return $image->user_id === $user->id || $image->is_public;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Image $image): bool
    {
        return $image->user_id === $user->id;
    }

    public function delete(User $user, Image $image): bool
    {
        return $image->user_id === $user->id;
    }

    public function restore(User $user, Image $image): bool
    {
        return $image->user_id === $user->id;
    }

    public function forceDelete(User $user, Image $image): bool
    {
        return $image->user_id === $user->id;
    }
}
