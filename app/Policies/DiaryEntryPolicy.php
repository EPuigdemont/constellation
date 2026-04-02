<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DiaryEntry;
use App\Models\EntityShare;
use App\Models\User;

class DiaryEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DiaryEntry $diaryEntry): bool
    {
        // Owner can always view
        if ($diaryEntry->user_id === $user->id) {
            return true;
        }

        // Check if entity is shared with this user
        return EntityShare::query()
            ->where('owner_id', $diaryEntry->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $diaryEntry->id)
            ->where('entity_type', 'diary_entry')
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DiaryEntry $diaryEntry): bool
    {
        return $diaryEntry->user_id === $user->id;
    }

    public function delete(User $user, DiaryEntry $diaryEntry): bool
    {
        return $diaryEntry->user_id === $user->id;
    }

    public function restore(User $user, DiaryEntry $diaryEntry): bool
    {
        return $diaryEntry->user_id === $user->id;
    }

    public function forceDelete(User $user, DiaryEntry $diaryEntry): bool
    {
        return $diaryEntry->user_id === $user->id;
    }
}
