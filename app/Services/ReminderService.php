<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Collection;

class ReminderService
{
    /**
     * Get all notifications for today: due reminders + matching important dates.
     *
     * @return Collection<int, array{type: string, title: string, id: string}>
     */
    public function getTodayNotifications(User $user): Collection
    {
        $notifications = collect();
        $today = now()->startOfDay();

        // Due reminders (remind_at <= today, not completed)
        Reminder::where('user_id', $user->id)
            ->where('is_completed', false)
            ->where('remind_at', '<=', now())
            ->get()
            ->each(fn (Reminder $r) => $notifications->push([
                'type' => 'reminder',
                'title' => $r->title,
                'id' => $r->id,
            ]));

        // Important dates matching today
        ImportantDate::where('user_id', $user->id)
            ->get()
            ->filter(function (ImportantDate $d) use ($today) {
                if ($d->date->isSameDay($today)) {
                    return true;
                }
                if ($d->recurs_annually && $d->date->month === $today->month && $d->date->day === $today->day) {
                    return true;
                }

                return false;
            })
            ->each(fn (ImportantDate $d) => $notifications->push([
                'type' => 'important_date',
                'title' => $d->label,
                'id' => $d->id,
            ]));

        return $notifications;
    }

    /**
     * "Sad entry" detection: when an entry tagged "sad" is saved,
     * look for a past entry tagged "happy" or "grateful" from roughly
     * 1 week, 1 month, or 1 year ago and return it as a gentle nudge.
     */
    public function findUpliftingEntry(User $user): ?DiaryEntry
    {
        $happyTags = ['happy', 'grateful', 'excited'];

        $windows = [
            now()->subWeek()->subDays(2) => now()->subWeek()->addDays(2),
            now()->subMonth()->subDays(3) => now()->subMonth()->addDays(3),
            now()->subYear()->subDays(7) => now()->subYear()->addDays(7),
        ];

        foreach ($windows as $from => $to) {
            $entry = DiaryEntry::where('user_id', $user->id)
                ->whereHas('tags', fn ($q) => $q->whereIn('name', $happyTags))
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->first();

            if ($entry) {
                return $entry;
            }
        }

        // Fallback: any happy entry at all
        return DiaryEntry::where('user_id', $user->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('name', $happyTags))
            ->inRandomOrder()
            ->first();
    }
}
