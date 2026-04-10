<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Calendar;
use App\Models\CalendarDayMood;
use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('calendar'))
            ->assertRedirect(route('login'));
    }

    public function test_previous_month_navigates_back(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Calendar::class);
        $currentYear = (int) now()->year;
        $currentMonth = (int) now()->month;

        $component->call('previousMonth');

        $expected = now()->subMonth();
        $component->assertSet('year', (int) $expected->year);
        $component->assertSet('month', (int) $expected->month);
    }

    public function test_next_month_navigates_forward(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Calendar::class);

        $component->call('nextMonth');

        $expected = now()->addMonth();
        $component->assertSet('year', (int) $expected->year);
        $component->assertSet('month', (int) $expected->month);
    }

    public function test_go_to_today_resets_to_current_month(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('nextMonth')
            ->call('nextMonth')
            ->call('goToToday')
            ->assertSet('year', (int) now()->year)
            ->assertSet('month', (int) now()->month)
            ->assertSet('selectedDate', now()->toDateString());
    }

    public function test_select_date_sets_selected_date(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('selectDate', $date)
            ->assertSet('selectedDate', $date);
    }

    public function test_select_same_date_twice_deselects_it(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('selectDate', $date)
            ->call('selectDate', $date)
            ->assertSet('selectedDate', '');
    }

    public function test_save_new_entity_creates_diary_entry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->set('createType', 'diary')
            ->set('createTitle', 'Calendar Diary')
            ->set('createBody', 'Written from calendar')
            ->call('saveNewEntity');

        $this->assertDatabaseHas('diary_entries', [
            'user_id' => $user->id,
            'title' => 'Calendar Diary',
        ]);
    }

    public function test_save_new_entity_creates_note(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->set('createType', 'note')
            ->set('createTitle', 'Calendar Note')
            ->call('saveNewEntity');

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'title' => 'Calendar Note',
        ]);
    }

    public function test_save_new_entity_resets_create_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->set('showCreateForm', true)
            ->set('createTitle', 'Test')
            ->call('saveNewEntity')
            ->assertSet('showCreateForm', false)
            ->assertSet('createTitle', '');
    }

    public function test_set_day_mood_creates_calendar_day_mood(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('setDayMood', $date, 'love');

        $this->assertDatabaseHas('calendar_day_moods', [
            'user_id' => $user->id,
            'date' => $date,
            'mood' => 'love',
        ]);
    }

    public function test_set_day_mood_empty_string_removes_mood(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        CalendarDayMood::create(['user_id' => $user->id, 'date' => $date, 'mood' => 'love']);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('setDayMood', $date, '');

        $this->assertDatabaseMissing('calendar_day_moods', ['user_id' => $user->id, 'date' => $date]);
    }

    public function test_open_entity_modal_sets_modal_state(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id, 'title' => 'Test Entry']);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openEntityModal', 'diary', $entry->id)
            ->assertSet('showEntityModal', true)
            ->assertSet('modalEntityType', 'diary')
            ->assertSet('modalEntityTitle', 'Test Entry');
    }

    public function test_close_entity_modal_hides_modal(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openEntityModal', 'diary', $entry->id)
            ->call('closeEntityModal')
            ->assertSet('showEntityModal', false);
    }

    public function test_delete_modal_entity_soft_deletes_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'title' => 'Calendar note',
        ]);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openEntityModal', 'note', $note->id)
            ->call('deleteModalEntity')
            ->assertSet('showEntityModal', false);

        $this->assertSoftDeleted('notes', ['id' => $note->id]);
    }

    public function test_open_modal_entity_in_canvas_redirects_to_canvas_editor(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::factory()->create([
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openEntityModal', 'reminder', $reminder->id)
            ->call('openModalEntityInCanvas')
            ->assertRedirect(route('canvas', [
                'edit_entity_id' => $reminder->id,
                'edit_entity_type' => 'reminder',
            ]));
    }

    public function test_open_create_form_opens_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openCreateForm', 'diary')
            ->assertSet('showCreateForm', true)
            ->assertSet('createType', 'diary');
    }

    public function test_close_create_form_resets_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('openCreateForm', 'note')
            ->set('createTitle', 'Something')
            ->call('closeCreateForm')
            ->assertSet('showCreateForm', false)
            ->assertSet('createTitle', '');
    }

    public function test_previous_month_clears_selected_date(): void
    {
        $user = User::factory()->create();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->call('selectDate', $date)
            ->call('previousMonth')
            ->assertSet('selectedDate', '');
    }
}
