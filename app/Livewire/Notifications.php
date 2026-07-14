<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\DiaryEntry;
use App\Models\EntityShare;
use App\Models\Friendship;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderApproachingNotification;
use App\Services\FriendshipService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifications')]
class Notifications extends Component
{
    public function mount(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
    }

    public function toggleReminderDone(string $id): void
    {
        $reminder = Reminder::where('user_id', Auth::id())->findOrFail($id);
        $reminder->update(['is_completed' => ! $reminder->is_completed]);
    }

    public function toggleDateDone(string $id): void
    {
        $date = ImportantDate::where('user_id', Auth::id())->findOrFail($id);
        $date->update(['is_done' => ! $date->is_done]);
    }

    public function acceptFriendRequest(string $friendshipId, FriendshipService $service): void
    {
        $service->acceptFriendRequest(Auth::user(), $friendshipId);
    }

    public function rejectFriendRequest(string $friendshipId, FriendshipService $service): void
    {
        $service->rejectFriendRequest(Auth::user(), $friendshipId);
    }

    public function dismissSharedItem(string $entityShareId): void
    {
        $share = EntityShare::findOrFail($entityShareId);
        $this->authorize('delete', $share);
        $share->delete();
    }

    public function render(): View
    {
        $user = Auth::user();
        $userId = $user->id;

        $friendRequests = Friendship::where('friend_id', $userId)
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->get();

        $sharedItems = $this->loadSharedItems($userId);

        $approachingNotifications = $user->notifications()
            ->where('type', ReminderApproachingNotification::class)
            ->whereNull('read_at')
            ->latest()
            ->get();

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

        $today = now();

        $todayDates = ImportantDate::where('user_id', $userId)
            ->whereMonth('date', $today->month)
            ->whereDay('date', $today->day)
            ->get();

        $upcomingDates = ImportantDate::where('user_id', $userId)
            ->get()
            ->filter(function (ImportantDate $date) use ($today): bool {
                $thisYear = Carbon::parse((string) $date->date)->setYear($today->year);
                if ($thisYear->isPast() && ! $thisYear->isToday()) {
                    $thisYear->addYear();
                }

                return ! $thisYear->isToday() && $thisYear->diffInDays($today) <= 30;
            });

        return view('livewire.notifications-view', [
            'friendRequests' => $friendRequests,
            'sharedItems' => $sharedItems,
            'approachingNotifications' => $approachingNotifications,
            'pendingReminders' => $pendingReminders,
            'completedReminders' => $completedReminders,
            'todayDates' => $todayDates,
            'upcomingDates' => $upcomingDates,
        ]);
    }

    /** @return Collection<int, array{share: EntityShare, title: string, type: string, url: string, sharer: User}> */
    private function loadSharedItems(int $userId): Collection
    {
        $shares = EntityShare::where('friend_id', $userId)
            ->with('owner')
            ->get();

        if ($shares->isEmpty()) {
            return collect();
        }

        $byType = $shares->groupBy('entity_type');
        $result = collect();

        foreach ($byType as $type => $typeShares) {
            $ids = $typeShares->pluck('entity_id');

            $entities = match ($type) {
                'diary_entry' => DiaryEntry::whereIn('id', $ids)->get()->keyBy('id'),
                'note' => Note::whereIn('id', $ids)->get()->keyBy('id'),
                'postit' => Postit::whereIn('id', $ids)->get()->keyBy('id'),
                'reminder' => Reminder::whereIn('id', $ids)->where('is_completed', false)->get()->keyBy('id'),
                default => collect(),
            };

            foreach ($typeShares as $share) {
                $entity = $entities->get($share->entity_id);
                if (! $entity) {
                    continue;
                }

                $result->push([
                    'share' => $share,
                    'title' => $this->entityTitle($entity, $type),
                    'type' => $type,
                    'url' => $this->entityUrl($type),
                    'sharer' => $share->owner,
                ]);
            }
        }

        return $result;
    }

    private function entityTitle(mixed $entity, string $type): string
    {
        return match ($type) {
            'diary_entry', 'note', 'reminder' => (string) ($entity->title ?? ''),
            'postit' => str((string) ($entity->body ?? ''))->limit(60)->toString(),
            default => '',
        };
    }

    private function entityUrl(string $type): string
    {
        return match ($type) {
            'diary_entry' => route('diary').'?showShared=1',
            'note' => route('notes').'?showShared=1',
            'reminder' => route('reminders').'?showShared=1',
            default => route('notifications'),
        };
    }
}
