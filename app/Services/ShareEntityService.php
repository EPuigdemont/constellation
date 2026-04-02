<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EntityShare;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShareEntityService
{
    /**
     * Get friends for a user (bidirectional), returns array with id and username.
     *
     * @return array<int, array{id: string, username: string, name: string}>
     */
    public function getFriendsForUser(User $user): array
    {
        $sentFriendships = Friendship::where('user_id', $user->id)
            ->pluck('friend_id')
            ->all();

        $receivedFriendships = Friendship::where('friend_id', $user->id)
            ->pluck('user_id')
            ->all();

        $friendIds = array_unique(array_merge($sentFriendships, $receivedFriendships));

        if (empty($friendIds)) {
            return [];
        }

        return User::whereIn('id', $friendIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (string) $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * Get currently shared friend IDs for an entity.
     *
     * @param string $entityId
     * @param string $entityType
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
     * @param User   $owner
     * @param string $entityId
     * @param string $entityType
     * @param array<int, string> $friendIds
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
            EntityShare::create([
                'owner_id' => $owner->id,
                'friend_id' => $friendId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
            ]);
        }
    }

    /**
     * Share entity with a single friend.
     */
    public function shareWithFriend(User $owner, string $entityId, string $entityType, string $friendId): void
    {
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


