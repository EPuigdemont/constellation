<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\ToastNotifications;
use App\Models\User;
use App\Notifications\FriendRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToastNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ToastNotifications::class)
            ->assertStatus(200);
    }

    public function test_poll_dispatches_toast_for_new_unread_notification(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $recipient = User::factory()->create();
        $recipient->notify(new FriendRequestNotification($sender));

        Livewire::actingAs($recipient)
            ->test(ToastNotifications::class)
            ->set('lastFetchedAt', now()->subMinutes(5)->toDateTimeString())
            ->call('poll')
            ->assertDispatched('toast-show');
    }

    public function test_poll_does_not_dispatch_for_notifications_created_before_mount(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $recipient->notify(new FriendRequestNotification($sender));

        Livewire::actingAs($recipient)
            ->test(ToastNotifications::class)
            ->call('poll')
            ->assertNotDispatched('toast-show');
    }

    public function test_poll_updates_last_fetched_at(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ToastNotifications::class);
        $before = $component->get('lastFetchedAt');

        $this->travel(2)->seconds();

        $component->call('poll');
        $after = $component->get('lastFetchedAt');

        $this->assertNotSame($before, $after);
    }

    public function test_mark_read_marks_notification_as_read(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $recipient->notify(new FriendRequestNotification($sender));
        $notificationId = $recipient->unreadNotifications()->first()->id;

        Livewire::actingAs($recipient)
            ->test(ToastNotifications::class)
            ->call('markRead', $notificationId);

        $this->assertNotNull($recipient->notifications()->find($notificationId)->read_at);
    }

    public function test_mark_read_ignores_notification_belonging_to_another_user(): void
    {
        $sender = User::factory()->create();
        $other = User::factory()->create();
        $current = User::factory()->create();

        $other->notify(new FriendRequestNotification($sender));
        $notificationId = $other->unreadNotifications()->first()->id;

        Livewire::actingAs($current)
            ->test(ToastNotifications::class)
            ->call('markRead', $notificationId);

        $this->assertNull($other->notifications()->find($notificationId)->read_at);
    }
}
