<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Tier;
use App\Livewire\ImagesGallery;
use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImagesGalleryTest extends TestCase
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
            ->test(ImagesGallery::class)
            ->assertStatus(200);
    }

    public function test_guest_gets_redirected(): void
    {
        $this->get(route('images'))
            ->assertRedirect(route('login'));
    }

    public function test_open_image_modal_sets_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImagesGallery::class)
            ->call('openImageModal', 'some-id', 'http://example.com/img.jpg', 'Alt text')
            ->assertSet('showImageModal', true)
            ->assertSet('modalImageId', 'some-id')
            ->assertSet('modalImageUrl', 'http://example.com/img.jpg')
            ->assertSet('modalImageAlt', 'Alt text');
    }

    public function test_close_image_modal_resets_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImagesGallery::class)
            ->call('openImageModal', 'some-id', 'http://example.com/img.jpg', 'Alt')
            ->call('closeImageModal')
            ->assertSet('showImageModal', false)
            ->assertSet('modalImageId', '')
            ->assertSet('modalImageUrl', '')
            ->assertSet('modalImageAlt', '');
    }

    public function test_delete_image_soft_deletes_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id, 'path' => 'images/test.jpg']);

        Livewire::actingAs($user)
            ->test(ImagesGallery::class)
            ->call('deleteImage', $image->id);

        $this->assertSoftDeleted('images', ['id' => $image->id]);
    }

    public function test_cannot_delete_another_users_image(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $other->id]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(ImagesGallery::class)
            ->call('deleteImage', $image->id);
    }

    public function test_delete_image_closes_modal(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id, 'path' => 'images/test.jpg']);

        Livewire::actingAs($user)
            ->test(ImagesGallery::class)
            ->call('openImageModal', $image->id, 'url', 'alt')
            ->call('deleteImage', $image->id)
            ->assertSet('showImageModal', false);
    }

    public function test_guest_user_sees_demo_images_in_gallery(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        Livewire::actingAs($guest)
            ->test(ImagesGallery::class)
            ->assertSee('Guest demo')
            ->assertSee('A cozy memory wall full of pinned notes, photos, and flowers.');
    }

    public function test_open_demo_image_modal_marks_image_as_demo(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        Livewire::actingAs($guest)
            ->test(ImagesGallery::class)
            ->call('openImageModal', 'guest-demo-memory-wall', 'http://example.com/demo.svg', 'Demo alt', true)
            ->assertSet('showImageModal', true)
            ->assertSet('modalImageIsDemo', true);
    }
}
