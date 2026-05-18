<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Notification;

class ReminderApproachingNotification extends Notification
{
    public function __construct(
        private readonly Reminder $reminder,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $minutesLeft = (int) now()->diffInMinutes($this->reminder->remind_at);
        $humanTime = $minutesLeft <= 1
            ? __('less than a minute')
            : __(':minutes minutes', ['minutes' => $minutesLeft]);

        return [
            'type' => 'reminder_approaching',
            'message' => __(':title — due in :time.', [
                'title' => $this->reminder->title,
                'time' => $humanTime,
            ]),
            'reminder_id' => $this->reminder->id,
            'remind_at' => $this->reminder->remind_at->toIso8601String(),
            'url' => route('notifications'),
        ];
    }
}
