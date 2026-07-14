<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderApproachingNotification;
use App\Notifications\ReminderDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DispatchReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_approaching_notification_within_default_window(): void
    {
        Notification::fake();

        $user = User::factory()->create(['reminder_approaching_minutes' => 30]);
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(20),
            'is_completed' => false,
            'approaching_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertSentTo($user, ReminderApproachingNotification::class);
    }

    public function test_sets_approaching_notified_at_after_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['reminder_approaching_minutes' => 30]);
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(20),
            'is_completed' => false,
            'approaching_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        $this->assertNotNull($reminder->fresh()->approaching_notified_at);
    }

    public function test_does_not_send_approaching_notification_if_already_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create(['reminder_approaching_minutes' => 30]);
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(20),
            'is_completed' => false,
            'approaching_notified_at' => now()->subMinutes(5),
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_does_not_send_approaching_for_reminder_outside_window(): void
    {
        Notification::fake();

        $user = User::factory()->create(['reminder_approaching_minutes' => 30]);
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(60),
            'is_completed' => false,
            'approaching_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_respects_user_reminder_approaching_minutes_setting(): void
    {
        Notification::fake();

        $user = User::factory()->create(['reminder_approaching_minutes' => 10]);
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addMinutes(20),
            'is_completed' => false,
            'approaching_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_sends_due_notification_for_overdue_reminder(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subMinutes(5),
            'is_completed' => false,
            'due_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertSentTo($user, ReminderDueNotification::class);
    }

    public function test_sets_due_notified_at_after_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subMinutes(5),
            'is_completed' => false,
            'due_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        $this->assertNotNull($reminder->fresh()->due_notified_at);
    }

    public function test_does_not_send_due_notification_if_already_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subMinutes(5),
            'is_completed' => false,
            'due_notified_at' => now()->subMinutes(1),
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_skips_completed_reminders(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subMinutes(5),
            'is_completed' => true,
            'due_notified_at' => null,
        ]);

        $this->artisan('notifications:dispatch-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
