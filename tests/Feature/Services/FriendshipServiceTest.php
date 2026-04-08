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
        $this->service = new FriendshipService();
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
}


