<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityShare;
use App\Models\Image;
use App\Models\User;
use App\Services\LimitCheckerService;

class ImagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Image $image): bool
    {
        if ($image->user_id === $user->id) {
            return true;
        }

        return EntityShare::query()
            ->where('owner_id', $image->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $image->id)
            ->where('entity_type', 'image')
            ->exists();
    }

    public function create(User $user): bool
    {
        // Guest users cannot upload images
        if ($user->isGuest()) {
            return false;
        }

        $limitChecker = app(LimitCheckerService::class);

        return $limitChecker->canCreateEntity($user, 'image');
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
