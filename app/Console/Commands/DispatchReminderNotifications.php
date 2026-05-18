<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderApproachingNotification;
use App\Notifications\ReminderDueNotification;
use Illuminate\Console\Command;

class DispatchReminderNotifications extends Command
{
    protected $signature = 'notifications:dispatch-reminders';

    protected $description = 'Dispatch approaching and due reminder notifications to users';

    public function handle(): int
    {
        User::all()->each(function (User $user): void {
            $window = (int) ($user->reminder_approaching_minutes ?? 30);

            Reminder::where('user_id', $user->id)
                ->where('is_completed', false)
                ->whereNull('approaching_notified_at')
                ->whereBetween('remind_at', [now(), now()->addMinutes($window)])
                ->get()
                ->each(function (Reminder $reminder) use ($user): void {
                    $user->notify(new ReminderApproachingNotification($reminder));
                    $reminder->update(['approaching_notified_at' => now()]);
                });

            Reminder::where('user_id', $user->id)
                ->where('is_completed', false)
                ->whereNull('due_notified_at')
                ->where('remind_at', '<=', now())
                ->get()
                ->each(function (Reminder $reminder) use ($user): void {
                    $user->notify(new ReminderDueNotification($reminder));
                    $reminder->update(['due_notified_at' => now()]);
                });
        });

        return self::SUCCESS;
    }
}
