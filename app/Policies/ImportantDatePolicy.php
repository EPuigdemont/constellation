<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportantDate;
use App\Models\User;

class ImportantDatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ImportantDate $importantDate): bool
    {
        return $importantDate->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ImportantDate $importantDate): bool
    {
        return $importantDate->user_id === $user->id;
    }

    public function delete(User $user, ImportantDate $importantDate): bool
    {
        return $importantDate->user_id === $user->id;
    }

    public function restore(User $user, ImportantDate $importantDate): bool
    {
        return $importantDate->user_id === $user->id;
    }

    public function forceDelete(User $user, ImportantDate $importantDate): bool
    {
        return $importantDate->user_id === $user->id;
    }
}
