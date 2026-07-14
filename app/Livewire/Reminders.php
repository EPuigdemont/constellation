<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Enums\ReminderType;
use App\Models\EntityShare;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Services\LimitCheckerService;
use App\Services\ShareEntityService;
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

    public bool $showShared = false;

    // Share panel state
    public bool $showSharePanel = false;

    public string $sharingReminderId = '';

    /** @var array<int, string> */
    public array $shareFriendIds = [];

    /** @var array<int, array{id: string, username: string, name: string}> */
    public array $availableFriends = [];

    public function mount(): void
    {
        $this->reminderAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->showShared = request()->boolean('showShared');
    }

    public function toggleShowShared(): void
    {
        $this->showShared = ! $this->showShared;
    }

    public function openSharePanel(string $reminderId, ShareEntityService $service): void
    {
        $reminder = Reminder::where('user_id', Auth::id())->findOrFail($reminderId);
        Gate::authorize('update', $reminder);
        $this->sharingReminderId = $reminderId;
        $this->availableFriends = $service->getFriendsForUser(Auth::user());
        $this->shareFriendIds = $service->getSharedFriendIds(Auth::user(), $reminderId, 'reminder');
        $this->showSharePanel = true;
    }

    public function saveShare(ShareEntityService $service): void
    {
        $service->syncShares(Auth::user(), $this->sharingReminderId, 'reminder', $this->shareFriendIds);
        $this->closeSharePanel();
    }

    public function closeSharePanel(): void
    {
        $this->showSharePanel = false;
        $this->sharingReminderId = '';
        $this->shareFriendIds = [];
        $this->availableFriends = [];
    }

    public function dismissSharedReminder(string $entityShareId): void
    {
        $share = EntityShare::findOrFail($entityShareId);
        Gate::authorize('delete', $share);
        $share->delete();
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
        $date->update(['is_done' => ! $date->is_done]);
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
                $this->limitError = __('You have reached your reminder limit for today. Remaining: :remaining.', ['remaining' => $remaining]);
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
            $existing = Reminder::where('user_id', Auth::id())->findOrFail($this->editingReminderId);
            Gate::authorize('update', $existing);
            if ($existing->remind_at->toDateTimeString() !== Carbon::parse($this->reminderAt)->toDateTimeString()) {
                $data['approaching_notified_at'] = null;
                $data['due_notified_at'] = null;
            }
            $existing->update($data);
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
        $reminder = Reminder::where('user_id', Auth::id())->findOrFail($id);
        Gate::authorize('delete', $reminder);
        $reminder->delete();
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

        $sharedReminders = collect();
        if ($this->showShared) {
            $shares = EntityShare::where('friend_id', $user->id)
                ->where('entity_type', 'reminder')
                ->with('owner')
                ->get();

            if ($shares->isNotEmpty()) {
                $sharedReminders = Reminder::whereIn('id', $shares->pluck('entity_id'))
                    ->where('is_completed', false)
                    ->orderBy('remind_at')
                    ->get()
                    ->each(function (Reminder $r) use ($shares): void {
                        $share = $shares->firstWhere('entity_id', $r->id);
                        $r->is_shared = true;
                        $r->shared_by = $share?->owner?->name;
                        $r->share_id = $share?->id;
                    });
            }
        }

        return view('livewire.reminders-view', [
            'importantDates' => $importantDates,
            'reminders' => $reminders,
            'sharedReminders' => $sharedReminders,
        ]);
    }

    private function reminderTypeValue(mixed $type): string
    {
        return $type instanceof ReminderType ? $type->value : (is_string($type) && $type !== '' ? $type : 'general');
    }
}
