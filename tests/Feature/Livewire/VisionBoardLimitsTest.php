<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Tier;
use App\Livewire\VisionBoard;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VisionBoardLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_cannot_upload_more_than_total_image_limit(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        Image::factory()->count(20)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(VisionBoard::class)
            ->set('imageUpload', UploadedFile::fake()->image('blocked.png'))
            ->call('uploadImage')
            ->assertSet('limitError', 'You have reached your image upload limit. Remaining: 0.');

        $this->assertDatabaseCount('images', 20);
    }
}

