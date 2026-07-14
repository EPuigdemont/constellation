<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\FriendRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FriendRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_returns_database(): void
    {
        $sender = User::factory()->create();
        $notification = new FriendRequestNotification($sender);

        $this->assertSame(['database'], $notification->via($sender));
    }

    public function test_to_array_type_is_friend_request(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $notification = new FriendRequestNotification($sender);

        $data = $notification->toArray($sender);

        $this->assertSame('friend_request', $data['type']);
    }

    public function test_to_array_message_includes_sender_name(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $notification = new FriendRequestNotification($sender);

        $data = $notification->toArray($sender);

        $this->assertStringContainsString('Alice', $data['message']);
    }

    public function test_to_array_includes_sender_name_field(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $notification = new FriendRequestNotification($sender);

        $data = $notification->toArray($sender);

        $this->assertSame('Alice', $data['sender_name']);
    }

    public function test_to_array_url_points_to_friends_route(): void
    {
        $sender = User::factory()->create();
        $notification = new FriendRequestNotification($sender);

        $data = $notification->toArray($sender);

        $this->assertSame(route('friends'), $data['url']);
    }

    public function test_notification_is_stored_when_sent(): void
    {
        Notification::fake();

        $sender = User::factory()->create(['name' => 'Alice']);
        $recipient = User::factory()->create();

        $recipient->notify(new FriendRequestNotification($sender));

        Notification::assertSentTo($recipient, FriendRequestNotification::class);
    }
}
