<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ItemSharedNotification;
use App\Services\FriendshipService;
use App\Services\ShareEntityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShareEntityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShareEntityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShareEntityService(new FriendshipService);
    }

    public function test_get_friends_for_user_includes_only_accepted_sent_friendships(): void
    {
        $owner = User::factory()->create();
        $accepted = User::factory()->create(['name' => 'Aaron Accepted']);
        $pending = User::factory()->create(['name' => 'Paula Pending']);
        $blocked = User::factory()->create(['name' => 'Brian Blocked']);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $accepted->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $pending->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $blocked->id,
            'status' => FriendshipStatus::Blocked->value,
        ]);

        $friends = $this->service->getFriendsForUser($owner);

        $this->assertCount(1, $friends);
        $this->assertSame((string) $accepted->id, $friends[0]['id']);
    }

    public function test_get_friends_for_user_includes_only_accepted_received_friendships(): void
    {
        $owner = User::factory()->create();
        $accepted = User::factory()->create(['name' => 'Aaron Accepted']);
        $pending = User::factory()->create(['name' => 'Paula Pending']);
        $blocked = User::factory()->create(['name' => 'Brian Blocked']);

        Friendship::create([
            'user_id' => $accepted->id,
            'friend_id' => $owner->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        Friendship::create([
            'user_id' => $pending->id,
            'friend_id' => $owner->id,
            'status' => FriendshipStatus::Pending->value,
        ]);

        Friendship::create([
            'user_id' => $blocked->id,
            'friend_id' => $owner->id,
            'status' => FriendshipStatus::Blocked->value,
        ]);

        $friends = $this->service->getFriendsForUser($owner);

        $this->assertCount(1, $friends);
        $this->assertSame((string) $accepted->id, $friends[0]['id']);
    }

    public function test_get_friends_for_user_keeps_name_ordering_for_accepted_friendships(): void
    {
        $owner = User::factory()->create();
        $zeta = User::factory()->create(['name' => 'Zeta']);
        $alpha = User::factory()->create(['name' => 'Alpha']);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $zeta->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        Friendship::create([
            'user_id' => $alpha->id,
            'friend_id' => $owner->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $friends = $this->service->getFriendsForUser($owner);

        $this->assertCount(2, $friends);
        $this->assertSame((string) $alpha->id, $friends[0]['id']);
        $this->assertSame((string) $zeta->id, $friends[1]['id']);
    }

    public function test_sync_shares_notifies_recipient_when_new_share_created(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->service->syncShares($owner, $note->id, 'note', [(string) $friend->id]);

        Notification::assertSentTo($friend, ItemSharedNotification::class);
    }

    public function test_sync_shares_skips_reminder_share_when_recipient_disallows_it(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $friend = User::factory()->create(['allow_shared_reminders' => false]);
        $reminder = Reminder::factory()->create(['user_id' => $owner->id]);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->service->syncShares($owner, $reminder->id, 'reminder', [(string) $friend->id]);

        $this->assertDatabaseMissing('entity_shares', [
            'friend_id' => $friend->id,
            'entity_id' => $reminder->id,
            'entity_type' => 'reminder',
        ]);
        Notification::assertNothingSent();
    }

    public function test_sync_shares_creates_share_but_skips_notification_when_recipient_disables_reminder_notifications(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $friend = User::factory()->create([
            'allow_shared_reminders' => true,
            'notify_shared_reminders' => false,
        ]);
        $reminder = Reminder::factory()->create(['user_id' => $owner->id]);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->service->syncShares($owner, $reminder->id, 'reminder', [(string) $friend->id]);

        $this->assertDatabaseHas('entity_shares', [
            'friend_id' => $friend->id,
            'entity_id' => $reminder->id,
            'entity_type' => 'reminder',
        ]);
        Notification::assertNothingSent();
    }
}
