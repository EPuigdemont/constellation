<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ImportantDate;
use App\Models\Reminder;
use Carbon\Carbon;
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
                $eventDate = Carbon::parse((string) $date->date);

                return ($eventDate->month === now()->month && $eventDate->day === now()->day)
                    || ($date->recurs_annually && $eventDate->month === now()->month && $eventDate->day === now()->day);
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
