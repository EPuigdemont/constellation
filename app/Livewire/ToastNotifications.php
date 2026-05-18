<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ToastNotifications extends Component
{
    public string $lastFetchedAt = '';

    public function mount(): void
    {
        $this->lastFetchedAt = now()->toDateTimeString();
    }

    public function poll(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $newNotifications = $user->unreadNotifications()
            ->where('created_at', '>', $this->lastFetchedAt)
            ->get();

        $this->lastFetchedAt = now()->toDateTimeString();

        foreach ($newNotifications as $notification) {
            $data = $notification->data;
            $this->dispatch(
                'toast-show',
                id: $notification->id,
                type: $data['type'] ?? 'info',
                message: $data['message'] ?? '',
                url: $data['url'] ?? route('notifications'),
            );
        }
    }

    public function markRead(string $notificationId): void
    {
        $notification = Auth::user()?->notifications()->find($notificationId);
        $notification?->markAsRead();
    }

    public function render(): View
    {
        return view('livewire.toast-notifications');
    }
}
