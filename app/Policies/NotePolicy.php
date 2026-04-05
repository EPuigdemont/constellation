<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityShare;
use App\Models\Note;
use App\Models\User;
use App\Services\LimitCheckerService;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        // Owner can always view
        if ($note->user_id === $user->id) {
            return true;
        }

        // Check if entity is shared with this user
        return EntityShare::query()
            ->where('owner_id', $note->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $note->id)
            ->where('entity_type', 'note')
            ->exists();
    }

    public function create(User $user): bool
    {
        $limitChecker = app(LimitCheckerService::class);

        return $limitChecker->canCreateEntity($user, 'note');
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function restore(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
