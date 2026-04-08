<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarServeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_returns_404_when_user_has_no_avatar(): void
    {
        $viewer = User::factory()->create();
        $subject = User::factory()->create(['avatar_path' => null, 'avatar_disk' => null]);

        $this->actingAs($viewer)
            ->get(route('avatar.serve', $subject))
            ->assertNotFound();
    }

    public function test_serves_avatar_file_when_exists(): void
    {
        $viewer = User::factory()->create();
        $subject = User::factory()->create();

        $fakePath = 'avatars/'.$subject->id.'/avatar.jpg';
        Storage::disk('private')->put($fakePath, 'fake-image-data');

        $subject->update(['avatar_path' => $fakePath, 'avatar_disk' => 'private']);

        $response = $this->actingAs($viewer)
            ->get(route('avatar.serve', $subject));

        $response->assertOk();
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control') ?? '');
        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control') ?? '');
    }

    public function test_returns_404_when_file_missing_from_disk(): void
    {
        $viewer = User::factory()->create();
        $subject = User::factory()->create([
            'avatar_path' => 'avatars/missing.jpg',
            'avatar_disk' => 'private',
        ]);

        $this->actingAs($viewer)
            ->get(route('avatar.serve', $subject))
            ->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $subject = User::factory()->create();

        $this->get(route('avatar.serve', $subject))
            ->assertRedirect(route('login'));
    }
}
