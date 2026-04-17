<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\Tier;
use App\Models\Image;
use App\Models\User;
use App\Services\EditorImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EditorImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private EditorImageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->service = new EditorImageService;

        config()->set('constellation.image_upload.max_bytes.default', 2 * 1024 * 1024);
        config()->set('constellation.image_upload.max_bytes.vip', 10 * 1024 * 1024);
    }

    public function test_store_with_valid_jpeg_creates_image_record(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $image = $this->service->store($user, $file);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($user->id, $image->user_id);
        $this->assertSame('private', $image->disk);
        $this->assertNotNull($image->path);
        $this->assertSame(100, $image->image_width);
        $this->assertSame(100, $image->image_height);
    }

    public function test_store_persists_file_to_private_disk(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.png');

        $image = $this->service->store($user, $file);

        Storage::disk('private')->assertExists($image->path);
    }

    public function test_store_saves_original_name_as_alt(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('my-photo.jpg');

        $image = $this->service->store($user, $file);

        $this->assertSame('my-photo.jpg', $image->alt);
    }

    public function test_store_throws_validation_exception_for_invalid_mime(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->expectException(ValidationException::class);
        $this->service->store($user, $file);
    }

    public function test_store_allows_vip_user_to_upload_up_to_10mb(): void
    {
        $user = User::factory()->create(['tier' => Tier::VIP->value]);
        $file = UploadedFile::fake()->image('vip-photo.jpg', 2200, 2200)->size(8 * 1024);

        $image = $this->service->store($user, $file);

        $this->assertNotNull($image->id);
    }

    public function test_store_optimizes_non_vip_file_over_2mb(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        $file = UploadedFile::fake()->image('large-photo.jpg', 2200, 2200)->size(3 * 1024);

        $image = $this->service->store($user, $file);

        Storage::disk('private')->assertExists($image->path);
        $storedSize = Storage::disk('private')->size($image->path);

        $this->assertLessThanOrEqual(2 * 1024 * 1024, $storedSize);
    }

    public function test_store_throws_validation_exception_when_oversized_file_cannot_be_optimized(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        $file = UploadedFile::fake()->image('too-tight-limit.jpg', 400, 400);
        config()->set('constellation.image_upload.max_bytes.default', 1);

        $this->expectException(ValidationException::class);
        $this->service->store($user, $file);
    }

    public function test_store_stores_image_in_user_specific_directory(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $image = $this->service->store($user, $file);

        $this->assertStringContainsString('editor-images/'.$user->id, $image->path);
    }

    public function test_store_accepts_webp(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.webp', 100, 100);

        // Should not throw
        $image = $this->service->store($user, $file);

        $this->assertNotNull($image->id);
    }
}
