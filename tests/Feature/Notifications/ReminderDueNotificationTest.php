<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderDueNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_returns_database(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);
        $notification = new ReminderDueNotification($reminder);

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_to_array_type_is_reminder_due(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);

        $data = new ReminderDueNotification($reminder)->toArray($user);

        $this->assertSame('reminder_due', $data['type']);
    }

    public function test_message_includes_reminder_title(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Submit report',
        ]);

        $data = new ReminderDueNotification($reminder)->toArray($user);

        $this->assertStringContainsString('Submit report', $data['message']);
    }

    public function test_message_indicates_due_now(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);

        $data = new ReminderDueNotification($reminder)->toArray($user);

        $this->assertStringContainsString('due now', $data['message']);
    }

    public function test_to_array_includes_reminder_id_and_url(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);

        $data = new ReminderDueNotification($reminder)->toArray($user);

        $this->assertSame($reminder->id, $data['reminder_id']);
        $this->assertSame(route('notifications'), $data['url']);
    }
}
