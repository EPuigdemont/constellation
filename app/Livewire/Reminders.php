<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Enums\ReminderType;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Services\LimitCheckerService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Reminders')]
class Reminders extends Component
{
    // Important dates form
    public bool $showDateForm = false;

    public string $dateLabel = '';

    public string $dateValue = '';

    public bool $dateRecurs = false;

    public string $editingDateId = '';

    // Reminders form
    public bool $showReminderForm = false;

    public string $reminderTitle = '';

    public string $reminderBody = '';

    public string $reminderAt = '';

    public string $editingReminderId = '';

    public string $reminderType = 'general';

    public string $tab = 'reminders';

    public string $limitError = '';

    public function mount(): void
    {
        $this->reminderAt = now()->addDay()->format('Y-m-d\TH:i');
    }

    // ── Important Dates CRUD ──

    public function openDateForm(?string $id = null): void
    {
        if ($id) {
            $date = ImportantDate::where('user_id', Auth::id())->findOrFail($id);
            $this->editingDateId = $date->id;
            $this->dateLabel = $date->label;
            $this->dateValue = Carbon::parse((string) $date->date)->format('Y-m-d');
            $this->dateRecurs = $date->recurs_annually;
        } else {
            $this->editingDateId = '';
            $this->dateLabel = '';
            $this->dateValue = now()->format('Y-m-d');
            $this->dateRecurs = false;
        }

        $this->showDateForm = true;
    }

    public function saveDate(): void
    {
        $this->validate([
            'dateLabel' => 'required|string|max:255',
            'dateValue' => 'required|date',
        ]);

        $data = [
            'user_id' => Auth::id(),
            'label' => $this->dateLabel,
            'date' => $this->dateValue,
            'recurs_annually' => $this->dateRecurs,
        ];

        if ($this->editingDateId) {
            ImportantDate::where('user_id', Auth::id())
                ->findOrFail($this->editingDateId)
                ->update($data);
        } else {
            ImportantDate::create($data);
        }

        $this->closeDateForm();
    }

    public function deleteDate(string $id): void
    {
        ImportantDate::where('user_id', Auth::id())->findOrFail($id)->delete();
    }

    public function toggleDateComplete(string $id): void
    {
        $date = ImportantDate::where('user_id', Auth::id())->findOrFail($id);
        $date->update(['is_done' => !$date->is_done]);
    }

    public function closeDateForm(): void
    {
        $this->showDateForm = false;
        $this->editingDateId = '';
        $this->dateLabel = '';
        $this->dateValue = '';
        $this->dateRecurs = false;
    }

    // ── Reminders CRUD ──

    public function openReminderForm(?string $id = null): void
    {
        if ($id) {
            $reminder = Reminder::where('user_id', Auth::id())->findOrFail($id);
            $this->editingReminderId = $reminder->id;
            $this->reminderTitle = $reminder->title;
            $this->reminderBody = $reminder->body ?? '';
            $this->reminderAt = Carbon::parse((string) $reminder->remind_at)->format('Y-m-d\TH:i');
            $this->reminderType = $this->reminderTypeValue($reminder->reminder_type);
        } else {
            $this->editingReminderId = '';
            $this->reminderTitle = '';
            $this->reminderBody = '';
            $this->reminderAt = now()->addDay()->format('Y-m-d\TH:i');
            $this->reminderType = 'general';
        }

        $this->showReminderForm = true;
    }

    public function saveReminder(): void
    {
        $this->validate([
            'reminderTitle' => 'required|string|max:255',
            'reminderAt' => 'required|date',
        ]);

        $user = Auth::user();

        // Check limit before creating if not editing
        if (! $this->editingReminderId) {
            $limitChecker = app(LimitCheckerService::class);
            if (! $limitChecker->canCreateEntity($user, 'reminder')) {
                $remaining = $limitChecker->getRemainingCount($user, 'reminder');
                $this->limitError = "You have reached your reminder limit for today. Remaining: {$remaining}.";
                $this->dispatch('notify-error', message: $this->limitError);

                return;
            }
        }

        $this->limitError = '';

        $data = [
            'user_id' => $user->id,
            'title' => $this->reminderTitle,
            'body' => $this->reminderBody,
            'remind_at' => $this->reminderAt,
            'mood' => Mood::tryFrom($user->activeTheme()) ?? Mood::Summer,
            'reminder_type' => ReminderType::tryFrom($this->reminderType) ?? ReminderType::General,
        ];

        if ($this->editingReminderId) {
            Reminder::where('user_id', Auth::id())
                ->findOrFail($this->editingReminderId)
                ->update($data);
        } else {
            Gate::authorize('create', Reminder::class);
            Reminder::create($data);
        }

        $this->closeReminderForm();
    }

    public function toggleComplete(string $id): void
    {
        $reminder = Reminder::where('user_id', Auth::id())->findOrFail($id);
        $reminder->update(['is_completed' => ! $reminder->is_completed]);
    }

    public function deleteReminder(string $id): void
    {
        Reminder::where('user_id', Auth::id())->findOrFail($id)->delete();
    }

    public function closeReminderForm(): void
    {
        $this->showReminderForm = false;
        $this->editingReminderId = '';
        $this->reminderTitle = '';
        $this->reminderBody = '';
        $this->reminderAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->reminderType = 'general';
    }

    public function render(): View
    {
        $user = Auth::user();

        $importantDates = ImportantDate::where('user_id', $user->id)
            ->orderBy('date')
            ->get();

        $reminders = Reminder::where('user_id', $user->id)
            ->orderBy('remind_at')
            ->get();

        return view('livewire.reminders-view', [
            'importantDates' => $importantDates,
            'reminders' => $reminders,
        ]);
    }

    private function reminderTypeValue(mixed $type): string
    {
        return $type instanceof ReminderType ? $type->value : (is_string($type) && $type !== '' ? $type : 'general');
    }
}
