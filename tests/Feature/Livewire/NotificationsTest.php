<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Notifications;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('notifications'))
            ->assertRedirect(route('login'));
    }

    public function test_toggle_reminder_done_marks_reminder_completed(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'is_completed' => false]);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('toggleReminderDone', $reminder->id);

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_completed' => true]);
    }

    public function test_toggle_reminder_done_unmarks_completed_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'is_completed' => true]);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('toggleReminderDone', $reminder->id);

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_completed' => false]);
    }

    public function test_toggle_date_done_marks_important_date_done(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id, 'is_done' => false]);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('toggleDateDone', $date->id);

        $this->assertDatabaseHas('important_dates', ['id' => $date->id, 'is_done' => true]);
    }

    public function test_cannot_toggle_another_users_reminder(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $other->id, 'is_completed' => false]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('toggleReminderDone', $reminder->id);
    }

    public function test_cannot_toggle_another_users_important_date(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $other->id]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('toggleDateDone', $date->id);
    }
}
