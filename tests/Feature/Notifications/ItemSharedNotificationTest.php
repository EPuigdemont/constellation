<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ItemSharedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ItemSharedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_returns_database(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'note', 'some-id');

        $this->assertSame(['database'], $notification->via($sharer));
    }

    public function test_to_array_type_is_item_shared(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'note', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertSame('item_shared', $data['type']);
    }

    public function test_to_array_message_includes_sharer_name(): void
    {
        $sharer = User::factory()->create(['name' => 'Bob']);
        $notification = new ItemSharedNotification($sharer, 'note', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertStringContainsString('Bob', $data['message']);
    }

    public function test_to_array_includes_entity_type_and_id(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'note', 'abc-123');

        $data = $notification->toArray($sharer);

        $this->assertSame('note', $data['entity_type']);
        $this->assertSame('abc-123', $data['entity_id']);
    }

    public function test_diary_entry_url_has_show_shared_param(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'diary_entry', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertStringContainsString(route('diary'), $data['url']);
        $this->assertStringContainsString('showShared=1', $data['url']);
    }

    public function test_note_url_has_show_shared_param(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'note', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertStringContainsString(route('notes'), $data['url']);
        $this->assertStringContainsString('showShared=1', $data['url']);
    }

    public function test_reminder_url_has_show_shared_param(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'reminder', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertStringContainsString(route('reminders'), $data['url']);
        $this->assertStringContainsString('showShared=1', $data['url']);
    }

    public function test_unknown_entity_type_url_falls_back_to_notifications_route(): void
    {
        $sharer = User::factory()->create();
        $notification = new ItemSharedNotification($sharer, 'postit', 'some-id');

        $data = $notification->toArray($sharer);

        $this->assertSame(route('notifications'), $data['url']);
    }

    public function test_notification_is_stored_when_sent(): void
    {
        Notification::fake();

        $sharer = User::factory()->create();
        $recipient = User::factory()->create();

        $recipient->notify(new ItemSharedNotification($sharer, 'note', 'some-id'));

        Notification::assertSentTo($recipient, ItemSharedNotification::class);
    }
}
