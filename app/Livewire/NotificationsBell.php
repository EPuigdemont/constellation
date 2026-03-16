<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ImportantDate;
use App\Models\Reminder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsBell extends Component
{
    public function getNotificationCount(): int
    {
        $userId = Auth::id();

        $pendingReminders = Reminder::where('user_id', $userId)
            ->where('is_completed', false)
            ->where('remind_at', '<=', now())
            ->count();

        $todayDates = ImportantDate::where('user_id', $userId)
            ->where('is_done', false)
            ->get()
            ->filter(function (ImportantDate $date): bool {
                return ($date->date->month === now()->month && $date->date->day === now()->day)
                    || ($date->recurs_annually && $date->date->month === now()->month && $date->date->day === now()->day);
            })
            ->count();

        return $pendingReminders + $todayDates;
    }

    public function render(): View
    {
        return view('livewire.notifications-bell', [
            'count' => $this->getNotificationCount(),
        ]);
    }
}
