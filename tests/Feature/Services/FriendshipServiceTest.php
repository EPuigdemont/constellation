<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipServiceTest extends TestCase
{
    use RefreshDatabase;

    private FriendshipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FriendshipService;
    }

    public function test_remove_friend_deletes_accepted_friendship_when_current_user_is_sender(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->service->removeFriend($user, (string) $friend->id);

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_remove_friend_only_deletes_accepted_rows_for_target_pair(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $pending = Friendship::create([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        $this->service->removeFriend($user, (string) $friend->id);

        $this->assertDatabaseHas('friendships', [
            'id' => $pending->id,
            'status' => FriendshipStatus::Pending->value,
        ]);
    }

    public function test_are_friends_returns_true_for_accepted_friendship_in_either_direction(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->assertTrue($this->service->areFriends($user, $friend));
        $this->assertTrue($this->service->areFriends($friend, $user));
    }

    public function test_are_friends_returns_false_for_pending_friendship(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        $this->assertFalse($this->service->areFriends($user, $friend));
    }

    public function test_send_friend_request_creates_pending_friendship(): void
    {
        $from = User::factory()->create();
        $to = User::factory()->create();

        $result = $this->service->sendFriendRequest($from, $to->email);

        $this->assertTrue($result);
        $this->assertDatabaseHas('friendships', [
            'user_id' => $from->id,
            'friend_id' => $to->id,
            'status' => FriendshipStatus::Pending->value,
        ]);
    }

    public function test_send_friend_request_returns_false_for_unknown_email(): void
    {
        $from = User::factory()->create();

        $result = $this->service->sendFriendRequest($from, 'nobody@example.com');

        $this->assertFalse($result);
        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_send_friend_request_returns_false_for_self(): void
    {
        $user = User::factory()->create();

        $result = $this->service->sendFriendRequest($user, $user->email);

        $this->assertFalse($result);
    }

    public function test_send_friend_request_returns_false_when_already_exists(): void
    {
        $from = User::factory()->create();
        $to = User::factory()->create();

        Friendship::create([
            'user_id' => $from->id,
            'friend_id' => $to->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        $result = $this->service->sendFriendRequest($from, $to->email);

        $this->assertFalse($result);
    }

    public function test_accept_friend_request_updates_status_to_accepted(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        $result = $this->service->acceptFriendRequest($user, $friendship->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);
    }

    public function test_accept_friend_request_returns_false_for_wrong_recipient(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $other->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        // $user is the sender, not the recipient — should not be able to accept
        $result = $this->service->acceptFriendRequest($user, $friendship->id);

        $this->assertFalse($result);
    }

    public function test_reject_friend_request_deletes_friendship(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        $result = $this->service->rejectFriendRequest($user, $friendship->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_get_friends_for_user_returns_accepted_friends_in_both_directions(): void
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend1->id, 'status' => FriendshipStatus::Accepted->value]);
        Friendship::create(['user_id' => $friend2->id, 'friend_id' => $user->id, 'status' => FriendshipStatus::Accepted->value]);

        $friends = $this->service->getFriendsForUser($user);

        $ids = $friends->pluck('id')->all();
        $this->assertContains($friend1->id, $ids);
        $this->assertContains($friend2->id, $ids);
    }

    public function test_get_pending_requests_returns_incoming_pending(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        Friendship::create(['user_id' => $sender->id, 'friend_id' => $user->id, 'status' => FriendshipStatus::Pending->value]);

        $pending = $this->service->getPendingRequests($user);

        $this->assertCount(1, $pending);
    }

    public function test_get_pending_outgoing_returns_sent_pending(): void
    {
        $user = User::factory()->create();
        $recipient = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $recipient->id, 'status' => FriendshipStatus::Pending->value]);

        $outgoing = $this->service->getPendingOutgoing($user);

        $this->assertCount(1, $outgoing);
    }
}
