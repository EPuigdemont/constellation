<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Notification;

class ReminderDueNotification extends Notification
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
        return [
            'type' => 'reminder_due',
            'message' => __(':title is due now.', ['title' => $this->reminder->title]),
            'reminder_id' => $this->reminder->id,
            'remind_at' => $this->reminder->remind_at->toIso8601String(),
            'url' => route('notifications'),
        ];
    }
}
