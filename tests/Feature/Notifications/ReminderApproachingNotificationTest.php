<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderApproachingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderApproachingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_via_returns_database(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);
        $notification = new ReminderApproachingNotification($reminder);

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_to_array_type_is_reminder_approaching(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(20),
        ]);

        $data = new ReminderApproachingNotification($reminder)->toArray($user);

        $this->assertSame('reminder_approaching', $data['type']);
    }

    public function test_message_includes_reminder_title(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Call dentist',
            'remind_at' => now()->addMinutes(20),
        ]);

        $data = new ReminderApproachingNotification($reminder)->toArray($user);

        $this->assertStringContainsString('Call dentist', $data['message']);
    }

    public function test_message_includes_minutes_remaining(): void
    {
        $this->travelTo(now()->startOfMinute());

        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(25),
        ]);

        $data = new ReminderApproachingNotification($reminder)->toArray($user);

        $this->assertStringContainsString('25', $data['message']);
    }

    public function test_message_uses_less_than_a_minute_when_under_two_minutes(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addSeconds(30),
        ]);

        $data = new ReminderApproachingNotification($reminder)->toArray($user);

        $this->assertStringContainsString('less than a minute', $data['message']);
    }

    public function test_to_array_includes_reminder_id_and_url(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(10),
        ]);

        $data = new ReminderApproachingNotification($reminder)->toArray($user);

        $this->assertSame($reminder->id, $data['reminder_id']);
        $this->assertSame(route('notifications'), $data['url']);
    }
}
