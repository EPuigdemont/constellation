<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Mood;
use App\Livewire\Canvas;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\Note;
use App\Models\Postit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesktopTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->assertStatus(200)
            ->assertSee('Diary Entry')
            ->assertSee('Note')
            ->assertSee('Post-it');
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('canvas'))
            ->assertRedirect(route('login'));
    }

    public function test_save_position_persists_to_database(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('savePosition', $diary->id, 'diary_entry', 100.0, 200.0, 1);

        $this->assertDatabaseHas('entity_positions', [
            'user_id' => $user->id,
            'entity_id' => $diary->id,
            'entity_type' => 'diary_entry',
            'x' => 100.0,
            'y' => 200.0,
        ]);
    }

    public function test_bring_to_front_returns_incremented_z_index(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create(['user_id' => $user->id]);

        EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $diary->id,
            'entity_type' => 'diary_entry',
            'x' => 100,
            'y' => 200,
            'z_index' => 5,
        ]);

        $result = Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('bringToFront', $diary->id, 'diary_entry');

        $result->assertSet('maxZIndex', 6);
    }

    public function test_create_postit_creates_model_and_position(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('createPostit');

        $this->assertDatabaseCount('postits', 1);
        $this->assertDatabaseCount('entity_positions', 1);

        $postit = Postit::first();
        $this->assertEquals($user->id, $postit->user_id);
    }

    public function test_save_editor_creates_diary_entry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->set('editorMode', 'diary')
            ->set('editorTitle', 'My Diary')
            ->set('editorBody', 'Today was great')
            ->set('editorMood', 'summer')
            ->call('saveEditor');

        $this->assertDatabaseHas('diary_entries', [
            'user_id' => $user->id,
            'title' => 'My Diary',
            'body' => 'Today was great',
            'mood' => 'summer',
        ]);
    }

    public function test_save_editor_creates_note(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->set('editorMode', 'note')
            ->set('editorTitle', 'My Note')
            ->set('editorBody', 'Some thoughts')
            ->set('editorMood', 'breeze')
            ->call('saveEditor');

        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'title' => 'My Note',
            'body' => 'Some thoughts',
            'mood' => 'breeze',
        ]);
    }

    public function test_delete_entity_soft_deletes_and_authorizes(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('deleteEntity', $note->id, 'note');

        $this->assertSoftDeleted('notes', ['id' => $note->id]);
    }

    public function test_cannot_delete_another_users_entity(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $other->id, 'is_public' => true]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('deleteEntity', $note->id, 'note')
            ->assertForbidden();
    }

    public function test_change_mood_updates_entity(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create([
            'user_id' => $user->id,
            'mood' => Mood::Plain,
        ]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('changeMood', $diary->id, 'diary_entry', 'love');

        $this->assertDatabaseHas('diary_entries', [
            'id' => $diary->id,
            'mood' => 'love',
        ]);
    }

    public function test_toggle_public_flips_flag(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('togglePublic', $note->id, 'note');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'is_public' => true,
        ]);
    }

    public function test_cannot_mutate_another_users_entity(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $other->id, 'is_public' => true]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('changeMood', $note->id, 'note', 'summer')
            ->assertForbidden();
    }

    public function test_save_zoom_persists(): void
    {
        $user = User::factory()->create(['desktop_zoom' => 1.0]);

        Livewire::actingAs($user)
            ->test(Canvas::class)
            ->call('saveZoom', 1.5);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'desktop_zoom' => 1.5,
        ]);
    }

    public function test_non_owner_can_open_readonly_modal_for_shared_note(): void
    {
        $owner = User::factory()->create(['username' => 'owner-user']);
        $viewer = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Shared note',
            'body' => '<p>Read only body</p>',
            'is_public' => true,
        ]);

        Livewire::actingAs($viewer)
            ->test(Canvas::class)
            ->call('openReadonlyModal', $note->id, 'note')
            ->assertSet('showReadonlyModal', true)
            ->assertSet('readonlyEntityType', 'note')
            ->assertSet('readonlyOwnerUsername', 'owner-user')
            ->assertSet('readonlyTitle', 'Shared note');
    }

    public function test_non_owner_cannot_open_readonly_modal_for_private_note(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);

        Livewire::actingAs($viewer)
            ->test(Canvas::class)
            ->call('openReadonlyModal', $note->id, 'note')
            ->assertForbidden();
    }

    public function test_close_readonly_modal_resets_state(): void
    {
        $owner = User::factory()->create(['username' => 'owner-user']);
        $viewer = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Shared note',
            'body' => 'Read only body',
            'is_public' => true,
        ]);

        Livewire::actingAs($viewer)
            ->test(Canvas::class)
            ->call('openReadonlyModal', $note->id, 'note')
            ->call('closeReadonlyModal')
            ->assertSet('showReadonlyModal', false)
            ->assertSet('readonlyEntityType', '')
            ->assertSet('readonlyOwnerUsername', '')
            ->assertSet('readonlyTitle', '')
            ->assertSet('readonlyBody', '')
            ->assertSet('readonlyImageUrl', '')
            ->assertSet('readonlyUpdatedAt', '');
    }
}
