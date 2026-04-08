<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_owner_can_serve_their_image(): void
    {
        $user = User::factory()->create();
        $path = 'editor-images/'.$user->id.'/photo.jpg';
        Storage::disk('private')->put($path, 'fake-image-content');

        $image = Image::factory()->create([
            'user_id' => $user->id,
            'path' => $path,
            'disk' => 'private',
        ]);

        $this->actingAs($user)
            ->get(route('images.serve', $image))
            ->assertOk();

        $response = $this->actingAs($user)->get(route('images.serve', $image));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('max-age=86400', $response->headers->get('Cache-Control') ?? '');
    }

    public function test_non_owner_cannot_view_private_image(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $path = 'editor-images/'.$owner->id.'/private.jpg';
        Storage::disk('private')->put($path, 'content');

        $image = Image::factory()->create([
            'user_id' => $owner->id,
            'path' => $path,
            'disk' => 'private',
            'is_public' => false,
        ]);

        $this->actingAs($viewer)
            ->get(route('images.serve', $image))
            ->assertForbidden();
    }

    public function test_returns_404_when_file_missing_from_disk(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create([
            'user_id' => $user->id,
            'path' => 'editor-images/'.$user->id.'/nonexistent.jpg',
            'disk' => 'private',
        ]);

        $this->actingAs($user)
            ->get(route('images.serve', $image))
            ->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $owner = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $owner->id]);

        $this->get(route('images.serve', $image))
            ->assertRedirect(route('login'));
    }
}
