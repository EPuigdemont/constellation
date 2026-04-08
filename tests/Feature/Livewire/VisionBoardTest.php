<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\VisionBoard;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VisionBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('vision-board'))
            ->assertRedirect(route('login'));
    }

    public function test_save_editor_updates_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id, 'title' => 'Old Title', 'alt' => 'Old Alt']);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('openEditModal', $image->id)
            ->set('editorTitle', 'New Title')
            ->set('editorAlt', 'New Alt')
            ->set('editorMood', 'love')
            ->call('saveEditor');

        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'title' => 'New Title',
            'alt' => 'New Alt',
            'mood' => 'love',
        ]);
    }

    public function test_save_editor_closes_modal(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('openEditModal', $image->id)
            ->set('editorTitle', 'Title')
            ->set('editorMood', 'plain')
            ->call('saveEditor')
            ->assertSet('showEditorModal', false);
    }

    public function test_cannot_edit_another_users_image(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $other->id]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('openEditModal', $image->id)
            ->assertForbidden();
    }

    public function test_delete_image_soft_deletes_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('deleteImage', $image->id);

        $this->assertSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_cannot_delete_another_users_image(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $other->id]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('deleteImage', $image->id)
            ->assertForbidden();
    }

    public function test_change_mood_updates_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id, 'mood' => 'plain']);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('changeMood', $image->id, 'breeze');

        $this->assertDatabaseHas('images', ['id' => $image->id, 'mood' => 'breeze']);
    }

    public function test_save_zoom_persists_vision_board_zoom(): void
    {
        $user = User::factory()->create(['vision_board_zoom' => 1.0]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('saveZoom', 1.5);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'vision_board_zoom' => 1.5]);
    }

    public function test_open_edit_modal_sets_editor_state(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id, 'title' => 'My Image', 'alt' => 'Alt text']);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('openEditModal', $image->id)
            ->assertSet('showEditorModal', true)
            ->assertSet('editingImageId', $image->id)
            ->assertSet('editorTitle', 'My Image')
            ->assertSet('editorAlt', 'Alt text');
    }

    public function test_toggle_tag_adds_and_removes_tag_from_editor(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->call('openEditModal', $image->id)
            ->set('editorTagIds', []);

        $someTagId = 'fake-tag-uuid';

        $component->call('toggleTag', $someTagId)
            ->assertSet('editorTagIds', [$someTagId]);

        $component->call('toggleTag', $someTagId)
            ->assertSet('editorTagIds', []);
    }

    public function test_cancel_link_search_resets_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->set('showLinkSearchModal', true)
            ->set('linkingSourceId', 'some-id')
            ->call('cancelLinkSearch')
            ->assertSet('showLinkSearchModal', false)
            ->assertSet('linkingSourceId', '')
            ->assertSet('linkSearchQuery', '');
    }
}
