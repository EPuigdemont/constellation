<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class ItemSharedNotification extends Notification
{
    public function __construct(
        private readonly User $sharer,
        private readonly string $entityType,
        private readonly string $entityId,
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
            'type' => 'item_shared',
            'message' => __(':name shared a :type with you.', [
                'name' => $this->sharer->name,
                'type' => $this->entityTypeLabel(),
            ]),
            'sharer_name' => $this->sharer->name,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'url' => $this->entityUrl(),
        ];
    }

    private function entityTypeLabel(): string
    {
        return match ($this->entityType) {
            'diary_entry' => __('diary entry'),
            'note' => __('note'),
            'postit' => __('post-it'),
            'reminder' => __('reminder'),
            default => __('item'),
        };
    }

    private function entityUrl(): string
    {
        return match ($this->entityType) {
            'diary_entry' => route('diary').'?showShared=1',
            'note' => route('notes').'?showShared=1',
            'reminder' => route('reminders').'?showShared=1',
            default => route('notifications'),
        };
    }
}
