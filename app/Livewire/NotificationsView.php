<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ImportantDate;
use App\Models\Reminder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifications')]
class NotificationsView extends Component
{
    public function toggleReminderDone(string $id): void
    {
        $reminder = Reminder::where('user_id', Auth::id())->findOrFail($id);
        $reminder->update(['is_completed' => !$reminder->is_completed]);
    }

    public function toggleDateDone(string $id): void
    {
        $date = ImportantDate::where('user_id', Auth::id())->findOrFail($id);
        $date->update(['is_done' => !$date->is_done]);
    }

    public function render(): View
    {
        $userId = Auth::id();

        $pendingReminders = Reminder::where('user_id', $userId)
            ->where('is_completed', false)
            ->where('remind_at', '<=', now())
            ->orderBy('remind_at')
            ->get();

        $completedReminders = Reminder::where('user_id', $userId)
            ->where('is_completed', true)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $allDates = ImportantDate::where('user_id', $userId)
            ->orderBy('date')
            ->get();

        $todayDates = $allDates->filter(function (ImportantDate $date): bool {
            return $date->date->month === now()->month && $date->date->day === now()->day;
        });

        $upcomingDates = $allDates->filter(function (ImportantDate $date) {
            $thisYear = $date->date->copy()->year(now()->year);
            if ($thisYear->isPast() && !$thisYear->isToday()) {
                $thisYear->addYear();
            }
            return !$thisYear->isToday() && $thisYear->diffInDays(now()) <= 30;
        });

        return view('livewire.notifications-view', [
            'pendingReminders' => $pendingReminders,
            'completedReminders' => $completedReminders,
            'todayDates' => $todayDates,
            'upcomingDates' => $upcomingDates,
        ]);
    }
}
