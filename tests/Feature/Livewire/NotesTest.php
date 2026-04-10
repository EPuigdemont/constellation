<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Notes;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('notes'))
            ->assertRedirect(route('login'));
    }

    public function test_save_editor_creates_note_for_today_by_default(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('openNoteModal')
            ->set('editorTitle', 'Today note')
            ->set('editorBody', 'Body text')
            ->call('saveEditor');

        $note = Note::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($note);
        $this->assertSame(now()->toDateString(), $note?->created_at?->toDateString());
    }

    public function test_save_editor_creates_note_for_specific_day(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('openNoteModal', '2026-03-18')
            ->set('editorTitle', 'Specific day note')
            ->set('editorBody', 'Body text')
            ->call('saveEditor');

        $note = Note::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($note);
        $this->assertSame('2026-03-18', $note?->created_at?->toDateString());
    }

    public function test_toggle_day_expansion_expands_and_collapses(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('toggleDayExpansion', '2026-03-18')
            ->assertSet('expandedDays', ['2026-03-18'])
            ->call('toggleDayExpansion', '2026-03-18')
            ->assertSet('expandedDays', []);
    }

    public function test_toggling_one_day_does_not_change_other_day_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('toggleDayExpansion', '2026-03-18')
            ->call('toggleDayExpansion', '2026-03-17')
            ->assertSet('expandedDays', ['2026-03-18', '2026-03-17'])
            ->call('toggleDayExpansion', '2026-03-17')
            ->assertSet('expandedDays', ['2026-03-18']);
    }

    public function test_save_editor_updates_existing_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old title',
            'body' => 'Old body',
        ]);

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('openEditModal', $note->id)
            ->set('editorTitle', 'Updated title')
            ->set('editorBody', 'Updated body')
            ->set('editorDate', '2026-04-05')
            ->call('saveEditor');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated title',
            'body' => 'Updated body',
        ]);

        $this->assertSame('2026-04-05', $note->fresh()?->created_at?->toDateString());
    }

    public function test_delete_from_editor_soft_deletes_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Notes::class)
            ->call('openEditModal', $note->id)
            ->call('deleteFromEditor')
            ->assertSet('showEditorModal', false);

        $this->assertSoftDeleted('notes', ['id' => $note->id]);
    }
}
