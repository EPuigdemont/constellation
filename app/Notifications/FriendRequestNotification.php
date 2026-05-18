<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification
{
    public function __construct(
        private readonly User $sender,
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
            'type' => 'friend_request',
            'message' => __(':name sent you a friend request.', ['name' => $this->sender->name]),
            'sender_name' => $this->sender->name,
            'url' => route('friends'),
        ];
    }
}
