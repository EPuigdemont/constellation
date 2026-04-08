<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\DiaryEntry;
use App\Models\Tag;
use App\Models\User;
use App\Services\DataImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->service = new DataImportService;
        $this->cleanTempDir();
    }

    protected function tearDown(): void
    {
        $this->cleanTempDir();
        parent::tearDown();
    }

    private function cleanTempDir(): void
    {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
    }

    private function buildZip(array $data): string
    {
        $tempDir = sys_get_temp_dir().'/test_import_'.uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir.'/data.json', json_encode($data));

        $zipPath = sys_get_temp_dir().'/test_import_'.uniqid().'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($tempDir.'/data.json', 'data.json');
        $zip->close();

        // Clean up temp dir
        unlink($tempDir.'/data.json');
        rmdir($tempDir);

        return $zipPath;
    }

    public function test_import_throws_for_invalid_zip(): void
    {
        $user = User::factory()->create();
        $notAZip = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($notAZip, 'not a zip');

        $this->expectException(\RuntimeException::class);
        $this->service->import($user, $notAZip);

        @unlink($notAZip);
    }

    public function test_import_throws_for_missing_data_json(): void
    {
        $user = User::factory()->create();

        $zipPath = sys_get_temp_dir().'/test_empty_'.uniqid().'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->close();

        $this->expectException(\RuntimeException::class);
        try {
            $this->service->import($user, $zipPath);
        } finally {
            @unlink($zipPath);
        }
    }

    public function test_import_imports_tags(): void
    {
        $user = User::factory()->create();
        $tagId = 'old-tag-uuid';

        $zipPath = $this->buildZip([
            'version' => 1,
            'tags' => [
                ['id' => $tagId, 'name' => 'imported-tag', 'color' => '#ff0000', 'created_at' => null, 'updated_at' => null],
            ],
        ]);

        $result = $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'imported-tag']);
        $this->assertSame(1, $result['entities']);
    }

    public function test_import_imports_diary_entries(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->buildZip([
            'version' => 1,
            'tags' => [],
            'diary_entries' => [
                [
                    'id' => 'old-diary-uuid',
                    'title' => 'Imported Diary',
                    'body' => '<p>Hello</p>',
                    'mood' => 'summer',
                    'color_override' => null,
                    'is_public' => false,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
        ]);

        $result = $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $this->assertDatabaseHas('diary_entries', ['user_id' => $user->id, 'title' => 'Imported Diary']);
        $this->assertGreaterThan(0, $result['entities']);
    }

    public function test_import_imports_notes(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->buildZip([
            'version' => 1,
            'notes' => [
                [
                    'id' => 'old-note-uuid',
                    'title' => 'Imported Note',
                    'body' => 'Body',
                    'mood' => 'plain',
                    'color_override' => null,
                    'is_public' => false,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
        ]);

        $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $this->assertDatabaseHas('notes', ['user_id' => $user->id, 'title' => 'Imported Note']);
    }

    public function test_import_applies_user_settings(): void
    {
        $user = User::factory()->create(['theme' => 'summer']);

        $zipPath = $this->buildZip([
            'version' => 1,
            'user_settings' => ['theme' => 'night'],
        ]);

        $result = $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $this->assertTrue($result['settings']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'theme' => 'night']);
    }

    public function test_import_reuses_existing_tag_by_name(): void
    {
        $user = User::factory()->create();
        $existingTag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'already-here']);

        $zipPath = $this->buildZip([
            'version' => 1,
            'tags' => [
                ['id' => 'some-old-id', 'name' => 'already-here', 'color' => null, 'created_at' => null, 'updated_at' => null],
            ],
        ]);

        $this->service->import($user, $zipPath);
        @unlink($zipPath);

        // Should not have created a duplicate
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_import_assigns_new_uuids_to_entities(): void
    {
        $user = User::factory()->create();
        $oldId = 'old-fixed-uuid-1234';

        $zipPath = $this->buildZip([
            'version' => 1,
            'diary_entries' => [
                [
                    'id' => $oldId,
                    'title' => 'UUID Test',
                    'body' => '',
                    'mood' => 'plain',
                    'color_override' => null,
                    'is_public' => false,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
        ]);

        $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $entry = DiaryEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertNotSame($oldId, $entry->id);
    }

    public function test_import_returns_counts(): void
    {
        $user = User::factory()->create();

        $zipPath = $this->buildZip([
            'version' => 1,
            'tags' => [],
            'notes' => [
                [
                    'id' => 'n1',
                    'title' => 'Note 1',
                    'body' => '',
                    'mood' => 'plain',
                    'color_override' => null,
                    'is_public' => false,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
                [
                    'id' => 'n2',
                    'title' => 'Note 2',
                    'body' => '',
                    'mood' => 'plain',
                    'color_override' => null,
                    'is_public' => false,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
        ]);

        $result = $this->service->import($user, $zipPath);
        @unlink($zipPath);

        $this->assertSame(2, $result['entities']);
        $this->assertSame(0, $result['images']);
    }
}
