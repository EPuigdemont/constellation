<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EntityShare;
use App\Models\User;
use App\Notifications\ItemSharedNotification;
use Illuminate\Support\Facades\Gate;

class ShareEntityService
{
    public function __construct(private readonly FriendshipService $friendshipService) {}

    /**
     * Get friends for a user (bidirectional), returns array with id and username.
     *
     * @return array<int, array{id: string, username: string, name: string}>
     */
    public function getFriendsForUser(User $user): array
    {
        return $this->friendshipService->getFriendsForUser($user)
            ->sortBy('name')
            ->map(fn (User $u): array => [
                'id' => (string) $u->id,
                'username' => $u->username,
                'name' => $u->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Get currently shared friend IDs for an entity.
     *
     * @return array<int, string>
     */
    public function getSharedFriendIds(User $owner, string $entityId, string $entityType): array
    {
        return EntityShare::where('owner_id', $owner->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->pluck('friend_id')
            ->all();
    }

    /**
     * Sync entity shares with a list of friend IDs.
     *
     * @param  array<int, string>  $friendIds
     */
    public function syncShares(User $owner, string $entityId, string $entityType, array $friendIds): void
    {
        $current = EntityShare::where('owner_id', $owner->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->pluck('friend_id')
            ->all();

        $toDelete = array_diff($current, $friendIds);
        $toCreate = array_diff($friendIds, $current);

        if (! empty($toDelete)) {
            EntityShare::where('owner_id', $owner->id)
                ->where('entity_id', $entityId)
                ->where('entity_type', $entityType)
                ->whereIn('friend_id', $toDelete)
                ->delete();
        }

        foreach ($toCreate as $friendId) {
            $recipient = User::find($friendId);

            if (! $recipient) {
                continue;
            }

            if ($entityType === 'reminder' && ! $recipient->allow_shared_reminders) {
                continue;
            }

            EntityShare::create([
                'owner_id' => $owner->id,
                'friend_id' => $friendId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
            ]);

            if ($entityType !== 'reminder' || $recipient->notify_shared_reminders) {
                $recipient->notify(new ItemSharedNotification($owner, $entityType, $entityId));
            }
        }
    }

    /**
     * Share entity with a single friend.
     */
    public function shareWithFriend(User $owner, string $entityId, string $entityType, string $friendId): void
    {
        Gate::authorize('create', EntityShare::class);
        EntityShare::firstOrCreate([
            'owner_id' => $owner->id,
            'friend_id' => $friendId,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
        ]);
    }

    /**
     * Stop sharing entity with a friend.
     */
    public function unshareWithFriend(User $owner, string $entityId, string $entityType, string $friendId): void
    {
        EntityShare::where('owner_id', $owner->id)
            ->where('friend_id', $friendId)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->delete();
    }

    /**
     * Check if an entity is shared with a specific user.
     */
    public function isSharedWith(User $owner, string $entityId, string $entityType, User $viewer): bool
    {
        return EntityShare::where('owner_id', $owner->id)
            ->where('friend_id', $viewer->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->exists();
    }
}
