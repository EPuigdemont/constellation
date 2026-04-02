<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarDayMood;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\EntityRelationship;
use App\Models\Image;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DataImportService
{
    /** @var array<string, string> Maps old UUIDs to new UUIDs */
    private array $idMap = [];

    /**
     * Import data from a constellation export ZIP file.
     *
     * @return array{entities: int, images: int, settings: bool}
     */
    public function import(User $user, string $zipPath): array
    {
        $tempDir = storage_path('app/temp/import_' . $user->id . '_' . time());
        mkdir($tempDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException(__('Could not open the import file.'));
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $jsonPath = $tempDir . '/data.json';
        if (! file_exists($jsonPath)) {
            $this->deleteDirectory($tempDir);
            throw new \RuntimeException(__('Invalid export file: missing data.json.'));
        }

        $json = file_get_contents($jsonPath);
        if ($json === false) {
            $this->deleteDirectory($tempDir);
            throw new \RuntimeException(__('Invalid export file: unreadable data.json.'));
        }

        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['version'])) {
            $this->deleteDirectory($tempDir);
            throw new \RuntimeException(__('Invalid export file: corrupt data.'));
        }

        $counts = DB::transaction(function () use ($user, $data, $tempDir) {
            $entityCount = 0;
            $imageCount = 0;
            $settingsApplied = false;
            $userId = max(1, (int) $user->id);

            // 1. Apply user settings
            if (! empty($data['user_settings'])) {
                $settings = $data['user_settings'];
                $user->update(array_filter([
                    'theme' => $settings['theme'] ?? null,
                    'language' => $settings['language'] ?? null,
                    'desktop_zoom' => $settings['desktop_zoom'] ?? null,
                    'vision_board_zoom' => $settings['vision_board_zoom'] ?? null,
                    'diary_display_mode' => $settings['diary_display_mode'] ?? null,
                ], fn ($v) => $v !== null));
                $settingsApplied = true;

                // Import avatar
                if (! empty($settings['avatar_filename'])) {
                    $avatarFile = $tempDir . '/avatar/' . $settings['avatar_filename'];
                    if (file_exists($avatarFile)) {
                        $avatarPath = 'avatars/' . $user->id . '/' . $settings['avatar_filename'];
                        $avatarContents = file_get_contents($avatarFile);
                        if ($avatarContents !== false) {
                            Storage::disk('private')->put($avatarPath, $avatarContents);
                        }
                        $user->update(['avatar_path' => $avatarPath, 'avatar_disk' => 'private']);
                    }
                }
            }

            // 2. Import tags (map old IDs to new IDs)
            foreach ($data['tags'] ?? [] as $tag) {
                $existing = Tag::where('user_id', $user->id)->where('name', $tag['name'])->first();
                if ($existing) {
                    $this->idMap[$tag['id']] = $existing->id;
                } else {
                    $newTag = new Tag();
                    $newTag->id = Str::uuid()->toString();
                    $newTag->user_id = $userId;
                    $newTag->name = $tag['name'];
                    $newTag->color = $tag['color'] ?? null;
                    $newTag->timestamps = false;
                    $newTag->created_at = $tag['created_at'] ? Carbon::parse($tag['created_at'])->toDateTimeString() : now()->toDateTimeString();
                    $newTag->updated_at = $tag['updated_at'] ? Carbon::parse($tag['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                    $newTag->save();
                    $this->idMap[$tag['id']] = $newTag->id;
                    $entityCount++;
                }
            }

            // 3. Import diary entries
            foreach ($data['diary_entries'] ?? [] as $entry) {
                $new = new DiaryEntry();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->title = $entry['title'];
                $new->body = $entry['body'];
                $new->mood = $entry['mood'];
                $new->color_override = $entry['color_override'] ?? null;
                $new->is_public = $entry['is_public'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;
                $new->save();
                $entityCount++;
            }

            // 4. Import notes
            foreach ($data['notes'] ?? [] as $entry) {
                $new = new Note();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->title = $entry['title'];
                $new->body = $entry['body'];
                $new->mood = $entry['mood'];
                $new->color_override = $entry['color_override'] ?? null;
                $new->is_public = $entry['is_public'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;
                $new->save();
                $entityCount++;
            }

            // 5. Import postits
            foreach ($data['postits'] ?? [] as $entry) {
                $new = new Postit();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->body = $entry['body'];
                $new->mood = $entry['mood'];
                $new->color_override = $entry['color_override'] ?? null;
                $new->is_public = $entry['is_public'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;
                $new->save();
                $entityCount++;
            }

            // 6. Import images (metadata + files)
            foreach ($data['images'] ?? [] as $entry) {
                $new = new Image();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->path = $entry['path'];
                $new->disk = $entry['disk'] ?? 'private';
                $new->alt = $entry['alt'] ?? null;
                $new->title = $entry['title'] ?? null;
                $new->mood = $entry['mood'];
                $new->color_override = $entry['color_override'] ?? null;
                $new->is_public = $entry['is_public'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;

                // Copy image file from export
                $sourceFile = $tempDir . '/images/' . $entry['path'];
                if (file_exists($sourceFile)) {
                    $imageContents = file_get_contents($sourceFile);
                    if ($imageContents !== false) {
                        Storage::disk($new->disk)->put($entry['path'], $imageContents);
                        $imageCount++;
                    }
                }

                $new->save();
                $entityCount++;
            }

            // 7. Import important dates
            foreach ($data['important_dates'] ?? [] as $entry) {
                $new = new ImportantDate();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->label = $entry['label'];
                $new->date = Carbon::parse($entry['date'])->toDateString();
                $new->recurs_annually = $entry['recurs_annually'] ?? false;
                $new->is_done = $entry['is_done'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;
                $new->save();
                $entityCount++;
            }

            // 8. Import reminders
            foreach ($data['reminders'] ?? [] as $entry) {
                $new = new Reminder();
                $newId = Str::uuid()->toString();
                $this->idMap[$entry['id']] = $newId;
                $new->id = $newId;
                $new->user_id = $userId;
                $new->title = $entry['title'];
                $new->body = $entry['body'] ?? null;
                $new->remind_at = Carbon::parse($entry['remind_at'])->toDateTimeString();
                $new->mood = $entry['mood'];
                $new->reminder_type = $entry['reminder_type'] ?? null;
                $new->is_completed = $entry['is_completed'] ?? false;
                $new->timestamps = false;
                $new->created_at = $entry['created_at'] ? Carbon::parse($entry['created_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->updated_at = $entry['updated_at'] ? Carbon::parse($entry['updated_at'])->toDateTimeString() : now()->toDateTimeString();
                $new->deleted_at = ! empty($entry['deleted_at']) ? Carbon::parse($entry['deleted_at'])->toDateTimeString() : null;
                $new->save();
                $entityCount++;
            }

            // 9. Import entity positions (remap entity IDs)
            foreach ($data['entity_positions'] ?? [] as $pos) {
                $newEntityId = $this->idMap[$pos['entity_id']] ?? null;
                if (! $newEntityId) {
                    continue;
                }

                EntityPosition::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'entity_id' => $newEntityId,
                        'entity_type' => $pos['entity_type'],
                    ],
                    [
                        'context' => $pos['context'] ?? null,
                        'x' => $pos['x'],
                        'y' => $pos['y'],
                        'z_index' => $pos['z_index'] ?? 0,
                        'width' => $pos['width'] ?? null,
                        'height' => $pos['height'] ?? null,
                        'is_hidden' => $pos['is_hidden'] ?? false,
                    ],
                );
            }

            // 10. Import calendar day moods
            foreach ($data['calendar_day_moods'] ?? [] as $mood) {
                CalendarDayMood::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $mood['date'],
                    ],
                    ['mood' => $mood['mood']],
                );
            }

            // 11. Import taggables (remap tag + entity IDs)
            foreach ($data['taggables'] ?? [] as $taggable) {
                $newTagId = $this->idMap[$taggable['tag_id']] ?? null;
                $newEntityId = $this->idMap[$taggable['taggable_id']] ?? null;
                if (! $newTagId || ! $newEntityId) {
                    continue;
                }

                DB::table('taggables')->insertOrIgnore([
                    'id' => Str::uuid()->toString(),
                    'tag_id' => $newTagId,
                    'taggable_id' => $newEntityId,
                    'taggable_type' => $taggable['taggable_type'],
                    'created_at' => now(),
                ]);
            }

            // 12. Import entity relationships (remap entity IDs)
            foreach ($data['entity_relationships'] ?? [] as $rel) {
                $newAId = $this->idMap[$rel['entity_a_id']] ?? null;
                $newBId = $this->idMap[$rel['entity_b_id']] ?? null;
                if (! $newAId || ! $newBId) {
                    continue;
                }

                EntityRelationship::create([
                    'entity_a_id' => $newAId,
                    'entity_a_type' => $rel['entity_a_type'],
                    'entity_b_id' => $newBId,
                    'entity_b_type' => $rel['entity_b_type'],
                    'relationship_type' => $rel['relationship_type'],
                    'direction' => $rel['direction'],
                ]);
            }

            return [
                'entities' => $entityCount,
                'images' => $imageCount,
                'settings' => $settingsApplied,
            ];
        });

        // Clean up
        $this->deleteDirectory($tempDir);

        return $counts;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
