<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;

class LimitCheckerService
{
    /**
     * Check if a user can create an entity of the specified type.
     */
    public function canCreateEntity(User $user, string $entityType): bool
    {
        $limits = $this->getLimitsForUser($user);

        if (! array_key_exists($entityType, $limits)) {
            return false;
        }

        if ($limits[$entityType] === null) {
            return true;
        }

        if ($this->isPerDayLimit($entityType)) {
            return $this->getUsageForToday($user, $entityType) < $limits[$entityType];
        }

        return $this->getTotalUsage($user, $entityType) < $limits[$entityType];
    }

    /**
     * Get the remaining count for a specific entity type.
     */
    public function getRemainingCount(User $user, string $entityType): int
    {
        $limits = $this->getLimitsForUser($user);

        if (! array_key_exists($entityType, $limits) || $limits[$entityType] === null) {
            return -1;
        }

        $used = $this->isPerDayLimit($entityType)
            ? $this->getUsageForToday($user, $entityType)
            : $this->getTotalUsage($user, $entityType);

        return max(0, $limits[$entityType] - $used);
    }

    /**
     * Get the current usage for today for a specific entity type.
     */
    public function getUsageForToday(User $user, string $entityType): int
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();

        return match ($entityType) {
            'note' => Note::withTrashed()->where('user_id', $user->id)
                ->whereBetween('created_at', [$today, $tomorrow])
                ->count(),
            'postit' => Postit::withTrashed()->where('user_id', $user->id)
                ->whereBetween('created_at', [$today, $tomorrow])
                ->count(),
            'diary_entry' => DiaryEntry::withTrashed()->where('user_id', $user->id)
                ->whereBetween('created_at', [$today, $tomorrow])
                ->count(),
            'reminder' => Reminder::withTrashed()->where('user_id', $user->id)
                ->whereBetween('created_at', [$today, $tomorrow])
                ->count(),
            default => 0,
        };
    }

    /**
     * Get the total usage for a specific entity type.
     */
    public function getTotalUsage(User $user, string $entityType): int
    {
        return match ($entityType) {
            'image' => Image::withTrashed()->where('user_id', $user->id)->count(),
            default => 0,
        };
    }

    /**
     * @return array<string, int|null>
     */
    private function getLimitsForUser(User $user): array
    {
        $tier = $user->tier->value;
        /** @var array<string, int|null>|null $tierLimits */
        $tierLimits = config("constellation.tiers.{$tier}");
        $tierLimits ??= [];

        return [
            'note' => $tierLimits['notes_per_day'] ?? null,
            'postit' => $tierLimits['postits_per_day'] ?? null,
            'diary_entry' => $tierLimits['diary_entries_per_day'] ?? null,
            'reminder' => $tierLimits['reminders_per_day'] ?? null,
            'image' => $tierLimits['images_total'] ?? null,
        ];
    }

    private function isPerDayLimit(string $entityType): bool
    {
        return in_array($entityType, ['note', 'postit', 'diary_entry', 'reminder'], true);
    }
}
