<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvatarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->service = new AvatarService;
    }

    public function test_upload_stores_file_and_updates_user(): void
    {
        $user = User::factory()->create(['avatar_path' => null, 'avatar_disk' => null]);
        $file = UploadedFile::fake()->image('avatar.jpg');

        $path = $this->service->upload($user, $file);

        Storage::disk('private')->assertExists($path);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar_path' => $path,
            'avatar_disk' => 'private',
        ]);
    }

    public function test_upload_deletes_existing_avatar_first(): void
    {
        $user = User::factory()->create();
        $first = UploadedFile::fake()->image('first.jpg');
        $firstPath = $this->service->upload($user, $first);

        Storage::disk('private')->assertExists($firstPath);

        $second = UploadedFile::fake()->image('second.jpg');
        $this->service->upload($user, $second);

        Storage::disk('private')->assertMissing($firstPath);
    }

    public function test_upload_returns_path_string(): void
    {
        $user = User::factory()->create(['avatar_path' => null, 'avatar_disk' => null]);
        $file = UploadedFile::fake()->image('avatar.png');

        $path = $this->service->upload($user, $file);

        $this->assertIsString($path);
        $this->assertStringStartsWith('avatars/', $path);
    }

    public function test_delete_removes_file_and_clears_user_fields(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');
        $path = $this->service->upload($user, $file);

        $this->service->delete($user);

        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar_path' => null,
            'avatar_disk' => null,
        ]);
    }

    public function test_delete_on_user_with_no_avatar_does_not_throw(): void
    {
        $user = User::factory()->create(['avatar_path' => null, 'avatar_disk' => null]);

        $this->service->delete($user);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'avatar_path' => null]);
    }
}
