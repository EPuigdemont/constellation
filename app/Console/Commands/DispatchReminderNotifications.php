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
        $now = now();

        User::chunk(200, function ($users) use ($now): void {
            foreach ($users as $user) {
                $window = (int) ($user->reminder_approaching_minutes ?? 30);

                $approaching = Reminder::where('user_id', $user->id)
                    ->where('is_completed', false)
                    ->whereNull('approaching_notified_at')
                    ->whereBetween('remind_at', [$now, $now->copy()->addMinutes($window)])
                    ->get();

                foreach ($approaching as $reminder) {
                    $user->notify(new ReminderApproachingNotification($reminder));
                }

                if ($approaching->isNotEmpty()) {
                    Reminder::whereIn('id', $approaching->pluck('id'))
                        ->update(['approaching_notified_at' => $now]);
                }

                $due = Reminder::where('user_id', $user->id)
                    ->where('is_completed', false)
                    ->whereNull('due_notified_at')
                    ->where('remind_at', '<=', $now)
                    ->get();

                foreach ($due as $reminder) {
                    $user->notify(new ReminderDueNotification($reminder));
                }

                if ($due->isNotEmpty()) {
                    Reminder::whereIn('id', $due->pluck('id'))
                        ->update(['due_notified_at' => $now]);
                }
            }
        });

        return self::SUCCESS;
    }
}
