<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DiaryEntry;
use App\Models\User;

class DiaryEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DiaryEntry $diaryEntry): bool
    {
        return $diaryEntry->user_id === $user->id || $diaryEntry->is_public;
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
