<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DiaryEntry;
use App\Models\EntityShare;
use App\Models\User;
use App\Services\LimitCheckerService;

class DiaryEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DiaryEntry $diaryEntry): bool
    {
        if ($diaryEntry->user_id === $user->id) {
            return true;
        }

        return EntityShare::query()
            ->where('owner_id', $diaryEntry->user_id)
            ->where('friend_id', $user->id)
            ->where('entity_id', $diaryEntry->id)
            ->where('entity_type', 'diary_entry')
            ->exists();
    }

    public function create(User $user): bool
    {
        $limitChecker = app(LimitCheckerService::class);

        return $limitChecker->canCreateEntity($user, 'diary_entry');
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
