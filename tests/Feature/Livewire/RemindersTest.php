<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Reminders;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('reminders'))
            ->assertRedirect(route('login'));
    }

    // ── Important Dates ──

    public function test_save_date_creates_important_date(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('dateLabel', 'Birthday')
            ->set('dateValue', '2026-06-15')
            ->set('dateRecurs', true)
            ->call('saveDate');

        $this->assertDatabaseHas('important_dates', [
            'user_id' => $user->id,
            'label' => 'Birthday',
            'recurs_annually' => true,
        ]);
    }

    public function test_save_date_resets_form_after_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('dateLabel', 'Event')
            ->set('dateValue', '2026-07-01')
            ->call('saveDate')
            ->assertSet('showDateForm', false)
            ->assertSet('dateLabel', '');
    }

    public function test_save_date_updates_existing_date(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id, 'label' => 'Old Label']);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openDateForm', $date->id)
            ->set('dateLabel', 'New Label')
            ->call('saveDate');

        $this->assertDatabaseHas('important_dates', ['id' => $date->id, 'label' => 'New Label']);
    }

    public function test_delete_date_removes_important_date(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('deleteDate', $date->id);

        $this->assertSoftDeleted('important_dates', ['id' => $date->id]);
    }

    public function test_toggle_date_complete_toggles_is_done(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id, 'is_done' => false]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('toggleDateComplete', $date->id);

        $this->assertDatabaseHas('important_dates', ['id' => $date->id, 'is_done' => true]);
    }

    public function test_open_date_form_with_id_populates_fields(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id, 'label' => 'My Event']);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openDateForm', $date->id)
            ->assertSet('dateLabel', 'My Event')
            ->assertSet('editingDateId', $date->id)
            ->assertSet('showDateForm', true);
    }

    public function test_close_date_form_resets_state(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openDateForm', $date->id)
            ->call('closeDateForm')
            ->assertSet('showDateForm', false)
            ->assertSet('editingDateId', '');
    }

    // ── Reminders ──

    public function test_save_reminder_creates_reminder(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('reminderTitle', 'Buy groceries')
            ->set('reminderBody', 'Milk and eggs')
            ->set('reminderAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveReminder');

        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'title' => 'Buy groceries',
        ]);
    }

    public function test_save_reminder_resets_form_after_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('reminderTitle', 'Test reminder')
            ->set('reminderAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveReminder')
            ->assertSet('showReminderForm', false)
            ->assertSet('reminderTitle', '');
    }

    public function test_save_reminder_updates_existing_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openReminderForm', $reminder->id)
            ->set('reminderTitle', 'New Title')
            ->call('saveReminder');

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'title' => 'New Title']);
    }

    public function test_toggle_complete_toggles_reminder_is_completed(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'is_completed' => false]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('toggleComplete', $reminder->id);

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_completed' => true]);
    }

    public function test_delete_reminder_soft_deletes_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('deleteReminder', $reminder->id);

        $this->assertSoftDeleted('reminders', ['id' => $reminder->id]);
    }

    public function test_open_reminder_form_with_id_populates_fields(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create(['user_id' => $user->id, 'title' => 'My Reminder']);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openReminderForm', $reminder->id)
            ->assertSet('reminderTitle', 'My Reminder')
            ->assertSet('editingReminderId', $reminder->id)
            ->assertSet('showReminderForm', true);
    }

    public function test_close_reminder_form_resets_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->call('openReminderForm')
            ->call('closeReminderForm')
            ->assertSet('showReminderForm', false)
            ->assertSet('editingReminderId', '')
            ->assertSet('reminderTitle', '');
    }

    public function test_save_reminder_requires_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('reminderTitle', '')
            ->set('reminderAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveReminder')
            ->assertHasErrors(['reminderTitle']);
    }
}
