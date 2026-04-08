<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Diary;
use App\Models\DiaryEntry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('diary'))
            ->assertRedirect(route('login'));
    }

    public function test_create_entry_creates_diary_entry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->set('newTitle', 'My New Entry')
            ->set('newBody', '<p>Hello world</p>')
            ->call('createEntry');

        $this->assertDatabaseHas('diary_entries', [
            'user_id' => $user->id,
            'title' => 'My New Entry',
        ]);
    }

    public function test_create_entry_resets_form_after_creation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->set('showNewEntryForm', true)
            ->set('newTitle', 'Entry')
            ->set('newBody', 'Body')
            ->call('createEntry')
            ->assertSet('showNewEntryForm', false)
            ->assertSet('newTitle', '')
            ->assertSet('newBody', '');
    }

    public function test_save_entry_updates_existing_entry(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('startEditing', $entry->id)
            ->set('editTitle', 'Updated Title')
            ->set('editBody', 'Updated body')
            ->call('saveEntry');

        $this->assertDatabaseHas('diary_entries', [
            'id' => $entry->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_cannot_edit_another_users_entry(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $other->id]);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('startEditing', $entry->id)
            ->assertForbidden();
    }

    public function test_cancel_editing_resets_state(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id, 'title' => 'Some Entry']);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('startEditing', $entry->id)
            ->call('cancelEditing')
            ->assertSet('editingEntryId', '')
            ->assertSet('editTitle', '')
            ->assertSet('editBody', '');
    }

    public function test_toggle_display_mode_switches_between_scroll_and_paginated(): void
    {
        $user = User::factory()->create(['diary_display_mode' => 'paginated']);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->assertSet('displayMode', 'paginated')
            ->call('toggleDisplayMode')
            ->assertSet('displayMode', 'scroll')
            ->call('toggleDisplayMode')
            ->assertSet('displayMode', 'paginated');
    }

    public function test_toggle_display_mode_persists_to_database(): void
    {
        $user = User::factory()->create(['diary_display_mode' => 'paginated']);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('toggleDisplayMode');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'diary_display_mode' => 'scroll']);
    }

    public function test_next_page_increments_page(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->count(6)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->assertSet('currentPage', 1)
            ->call('nextPage')
            ->assertSet('currentPage', 2);
    }

    public function test_previous_page_decrements_page(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->count(6)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('nextPage')
            ->assertSet('currentPage', 2)
            ->call('previousPage')
            ->assertSet('currentPage', 1);
    }

    public function test_previous_page_does_not_go_below_one(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('previousPage')
            ->assertSet('currentPage', 1);
    }

    public function test_open_new_entry_shows_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('openNewEntry')
            ->assertSet('showNewEntryForm', true);
    }

    public function test_cancel_new_entry_hides_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('openNewEntry')
            ->call('cancelNewEntry')
            ->assertSet('showNewEntryForm', false);
    }

    public function test_change_mood_updates_entry(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id, 'mood' => 'plain']);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('changeMood', $entry->id, 'love');

        $this->assertDatabaseHas('diary_entries', ['id' => $entry->id, 'mood' => 'love']);
    }

    public function test_toggle_edit_tag_adds_and_removes_tag_id(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('toggleEditTag', $tag->id)
            ->assertSet('editTagIds', [$tag->id]);

        $component->call('toggleEditTag', $tag->id)
            ->assertSet('editTagIds', []);
    }

    public function test_create_edit_tag_inline_creates_tag_and_adds_to_edit_list(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->call('createEditTagInline', 'new-tag');

        $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'new-tag']);
    }

    public function test_dismiss_uplift_clears_uplift_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->set('upliftTitle', 'A happy memory')
            ->set('upliftPreview', 'Preview text')
            ->call('dismissUplift')
            ->assertSet('upliftTitle', null)
            ->assertSet('upliftPreview', null);
    }
}
