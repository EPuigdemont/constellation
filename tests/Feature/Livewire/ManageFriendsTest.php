<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\FriendshipStatus;
use App\Livewire\Actions\ManageFriends;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageFriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('friends'))
            ->assertRedirect(route('login'));
    }

    public function test_add_friend_sends_request_for_valid_email(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->set('newFriendEmail', $target->email)
            ->call('addFriend')
            ->assertSet('successMessage', __('Friend request sent to :email', ['email' => $target->email]))
            ->assertSet('newFriendEmail', '');

        $this->assertDatabaseHas('friendships', [
            'user_id' => $user->id,
            'friend_id' => $target->id,
            'status' => FriendshipStatus::Pending->value,
        ]);
    }

    public function test_add_friend_shows_error_for_empty_email(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->set('newFriendEmail', '')
            ->call('addFriend')
            ->assertSet('errorMessage', __('Please enter an email address'));
    }

    public function test_add_friend_shows_error_for_own_email(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->set('newFriendEmail', $user->email)
            ->call('addFriend')
            ->assertSet('errorMessage', __('You cannot add yourself as a friend'));
    }

    public function test_add_friend_shows_error_for_unknown_email(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->set('newFriendEmail', 'nobody@example.com')
            ->call('addFriend')
            ->assertSet('errorMessage', __('Could not send friend request. User may not exist or request already sent.'));
    }

    public function test_accept_request_updates_friendship_to_accepted(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->call('acceptRequest', $friendship->id)
            ->assertSet('successMessage', __('Friend request accepted'));

        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);
    }

    public function test_reject_request_deletes_pending_friendship(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        $friendship = Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $user->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->call('rejectRequest', $friendship->id)
            ->assertSet('successMessage', __('Friend request rejected'));

        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_remove_friend_deletes_accepted_friendship(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        Livewire::actingAs($user)
            ->test(ManageFriends::class)
            ->call('removeFriend', $friend->id)
            ->assertSet('successMessage', __('Friend removed'));

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);
    }
}
