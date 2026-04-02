<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FriendshipService
{
    /**
     * Send a friend request by email.
     */
    public function sendFriendRequest(User $from, string $email): bool
    {
        $to = User::where('email', $email)->first();

        if (!$to) {
            return false; // User not found
        }

        if ($from->id === $to->id) {
            return false; // Can't friend yourself
        }

        // Check if friendship already exists
        if (Friendship::where('user_id', $from->id)->where('friend_id', $to->id)->exists()) {
            return false;
        }

        // Check for reverse friendship
        if (Friendship::where('user_id', $to->id)->where('friend_id', $from->id)->exists()) {
            return false;
        }

        Friendship::create([
            'user_id' => $from->id,
            'friend_id' => $to->id,
            'status' => 'pending',
        ]);

        return true;
    }

    /**
     * Accept a friend request.
     */
    public function acceptFriendRequest(User $user, string $friendshipId): bool
    {
        $friendship = Friendship::where('id', $friendshipId)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return false;
        }

        $friendship->update(['status' => 'accepted']);

        return true;
    }

    /**
     * Reject or cancel a friend request.
     */
    public function rejectFriendRequest(User $user, string $friendshipId): bool
    {
        $friendship = Friendship::where('id', $friendshipId)
            ->where(function ($query) use ($user) {
                $query->where('friend_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return false;
        }

        $friendship->delete();

        return true;
    }

    /**
     * Remove a friend.
     */
    public function removeFriend(User $user, string $friendId): bool
    {
        Friendship::where(function ($query) use ($user, $friendId) {
            $query->where('user_id', $user->id)->where('friend_id', $friendId)
                ->orWhere('user_id', $friendId)->where('friend_id', $user->id);
        })
            ->where('status', 'accepted')
            ->delete();

        return true;
    }

    /**
     * Check if two users are friends.
     */
    public function areFriends(User $user1, User $user2): bool
    {
        return Friendship::where(function ($query) use ($user1, $user2) {
            $query->where('user_id', $user1->id)->where('friend_id', $user2->id)
                ->orWhere('user_id', $user2->id)->where('friend_id', $user1->id);
        })
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Get all accepted friends for a user.
     *
     * @return Collection<int, User>
     */
    public function getFriendsForUser(User $user): Collection
    {
        $sent = Friendship::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->pluck('friend_id');

        $received = Friendship::where('friend_id', $user->id)
            ->where('status', 'accepted')
            ->pluck('user_id');

        $friendIds = $sent->merge($received)->unique();

        return User::whereIn('id', $friendIds)->get();
    }

    /**
     * Get pending friend requests for a user.
     *
     * @return Collection<int, Friendship>
     */
    public function getPendingRequests(User $user): Collection
    {
        return $user->friendRequestsReceived()
            ->where('status', 'pending')
            ->with('user')
            ->get();
    }

    /**
     * Get pending outgoing friend requests.
     *
     * @return Collection<int, Friendship>
     */
    public function getPendingOutgoing(User $user): Collection
    {
        return $user->friendships()
            ->where('status', 'pending')
            ->with('friend')
            ->get();
    }
}

