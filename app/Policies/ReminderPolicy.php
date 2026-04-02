<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityShare;
use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reminder $reminder): bool
    {
        if ($reminder->user_id === $user->id) {
            return true;
        }

        return EntityShare::query()
            ->where('owner_id', $reminder->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $reminder->id)
            ->where('entity_type', 'reminder')
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function restore(User $user, Reminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function forceDelete(User $user, Reminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }
}
