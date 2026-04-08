<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->service = new DataExportService;
    }

    public function test_export_returns_path_to_zip_file(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->service->export($user);

        $this->assertFileExists($zipPath);
        $this->assertStringEndsWith('.zip', $zipPath);

        @unlink($zipPath);
    }

    public function test_export_zip_contains_data_json(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $this->assertSame(true, $zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('data.json'));
        $zip->close();

        @unlink($zipPath);
    }

    public function test_export_json_includes_diary_entries(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $user->id, 'title' => 'My Export Entry']);

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertNotEmpty($data['diary_entries']);
        $this->assertSame('My Export Entry', $data['diary_entries'][0]['title']);
    }

    public function test_export_json_includes_notes(): void
    {
        $user = User::factory()->create();
        Note::factory()->create(['user_id' => $user->id]);

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['notes']);
    }

    public function test_export_json_includes_tags(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => 'my-tag']);

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertCount(1, $data['tags']);
        $this->assertSame('my-tag', $data['tags'][0]['name']);
    }

    public function test_export_json_includes_user_settings(): void
    {
        $user = User::factory()->create(['theme' => 'night']);

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertArrayHasKey('user_settings', $data);
        $this->assertSame('night', $data['user_settings']['theme']);
    }

    public function test_export_json_has_version_key(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertArrayHasKey('version', $data);
        $this->assertSame(1, $data['version']);
    }

    public function test_export_excludes_other_users_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $other->id, 'title' => 'Other User Entry']);

        $zipPath = $this->service->export($user);

        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $json = $zip->getFromName('data.json');
        $zip->close();
        @unlink($zipPath);

        $data = json_decode($json, true);
        $this->assertEmpty($data['diary_entries']);
    }
}
