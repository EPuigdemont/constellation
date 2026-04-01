<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class UnverifiedUserCleanupService
{
    public function purgeOlderThanHours(int $hours): int
    {
        return User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();
    }
}

