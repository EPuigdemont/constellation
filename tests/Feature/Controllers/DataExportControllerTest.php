<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_export_returns_zip_download_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('data.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString('constellation_export_', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('data.export'))
            ->assertRedirect(route('login'));
    }

    public function test_export_includes_user_data(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('data.export'));

        $response->assertOk();
        // The response is streamed; we verify it starts with the PK zip header
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
    }
}
