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
use ZipArchive;

class DataExportService
{
    public function export(User $user): string
    {
        $userId = $user->id;

        $tags = Tag::where('user_id', $userId)->get();
        $diaryEntries = DiaryEntry::withTrashed()->where('user_id', $userId)->get();
        $notes = Note::withTrashed()->where('user_id', $userId)->get();
        $postits = Postit::withTrashed()->where('user_id', $userId)->get();
        $images = Image::withTrashed()->where('user_id', $userId)->get();
        $importantDates = ImportantDate::withTrashed()->where('user_id', $userId)->get();
        $reminders = Reminder::withTrashed()->where('user_id', $userId)->get();
        $positions = EntityPosition::where('user_id', $userId)->get();
        $calendarMoods = CalendarDayMood::where('user_id', $userId)->get();

        // Collect all entity IDs for relationship and taggable queries
        $entityIds = collect()
            ->merge($diaryEntries->pluck('id'))
            ->merge($notes->pluck('id'))
            ->merge($postits->pluck('id'))
            ->merge($images->pluck('id'))
            ->merge($importantDates->pluck('id'))
            ->merge($reminders->pluck('id'))
            ->values()
            ->all();

        $relationships = EntityRelationship::where(function ($q) use ($entityIds) {
            $q->whereIn('entity_a_id', $entityIds)
                ->orWhereIn('entity_b_id', $entityIds);
        })->get();

        $taggables = DB::table('taggables')
            ->whereIn('tag_id', $tags->pluck('id'))
            ->get();

        $data = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'user_settings' => [
                'theme' => $user->theme,
                'automatic_themes' => $user->automatic_themes,
                'language' => $user->language,
                'desktop_zoom' => $user->desktop_zoom,
                'vision_board_zoom' => $user->vision_board_zoom,
                'diary_display_mode' => $user->diary_display_mode,
            ],
            'tags' => $tags->map(fn (Tag $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'created_at' => $t->created_at?->toIso8601String(),
                'updated_at' => $t->updated_at?->toIso8601String(),
            ])->values()->all(),
            'diary_entries' => $diaryEntries->map(fn (DiaryEntry $e): array => [
                'id' => $e->id,
                'title' => $e->title,
                'body' => $e->body,
                'mood' => $this->enumValue($e->mood),
                'color_override' => $e->color_override,
                'is_public' => $e->is_public,
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
                'deleted_at' => $e->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'notes' => $notes->map(fn (Note $e): array => [
                'id' => $e->id,
                'title' => $e->title,
                'body' => $e->body,
                'mood' => $this->enumValue($e->mood),
                'color_override' => $e->color_override,
                'is_public' => $e->is_public,
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
                'deleted_at' => $e->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'postits' => $postits->map(fn (Postit $e): array => [
                'id' => $e->id,
                'body' => $e->body,
                'mood' => $this->enumValue($e->mood),
                'color_override' => $e->color_override,
                'is_public' => $e->is_public,
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
                'deleted_at' => $e->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'images' => $images->map(fn (Image $e): array => [
                'id' => $e->id,
                'path' => $e->path,
                'disk' => $e->disk,
                'alt' => $e->alt,
                'title' => $e->title,
                'mood' => $this->enumValue($e->mood),
                'color_override' => $e->color_override,
                'is_public' => $e->is_public,
                'created_at' => $e->created_at?->toIso8601String(),
                'updated_at' => $e->updated_at?->toIso8601String(),
                'deleted_at' => $e->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'important_dates' => $importantDates->map(fn (ImportantDate $d): array => [
                'id' => $d->id,
                'label' => $d->label,
                'date' => Carbon::parse((string) $d->date)->toDateString(),
                'recurs_annually' => $d->recurs_annually,
                'is_done' => $d->is_done,
                'created_at' => $d->created_at?->toIso8601String(),
                'updated_at' => $d->updated_at?->toIso8601String(),
                'deleted_at' => $d->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'reminders' => $reminders->map(fn (Reminder $r): array => [
                'id' => $r->id,
                'title' => $r->title,
                'body' => $r->body,
                'remind_at' => Carbon::parse((string) $r->remind_at)->toIso8601String(),
                'mood' => $this->enumValue($r->mood),
                'reminder_type' => $this->enumValue($r->reminder_type),
                'is_completed' => $r->is_completed,
                'created_at' => $r->created_at?->toIso8601String(),
                'updated_at' => $r->updated_at?->toIso8601String(),
                'deleted_at' => $r->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'entity_positions' => $positions->map(fn (EntityPosition $p): array => [
                'id' => $p->id,
                'entity_id' => $p->entity_id,
                'entity_type' => $p->entity_type,
                'context' => $p->context,
                'x' => $p->x,
                'y' => $p->y,
                'z_index' => $p->z_index,
                'width' => $p->width,
                'height' => $p->height,
                'is_hidden' => $p->is_hidden,
            ])->values()->all(),
            'calendar_day_moods' => $calendarMoods->map(fn (CalendarDayMood $m): array => [
                'id' => $m->id,
                'date' => Carbon::parse((string) $m->date)->toDateString(),
                'mood' => $m->mood,
            ])->values()->all(),
            'entity_relationships' => $relationships->map(fn (EntityRelationship $r): array => [
                'id' => $r->id,
                'entity_a_id' => $r->entity_a_id,
                'entity_a_type' => $r->entity_a_type,
                'entity_b_id' => $r->entity_b_id,
                'entity_b_type' => $r->entity_b_type,
                'relationship_type' => $this->enumValue($r->relationship_type) ?? '',
                'direction' => $this->enumValue($r->direction) ?? '',
            ])->values()->all(),
            'taggables' => $taggables->map(fn (object $t): array => [
                'tag_id' => $t->tag_id,
                'taggable_id' => $t->taggable_id,
                'taggable_type' => $t->taggable_type,
            ])->values()->all(),
        ];

        $tempDir = storage_path('app/temp/export_'.$userId.'_'.time());
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        file_put_contents($tempDir.'/data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Copy image files into the zip
        $imagesDir = $tempDir.'/images';
        mkdir($imagesDir, 0755, true);

        foreach ($images as $image) {
            $disk = Storage::disk($image->disk ?? 'private');
            if ($disk->exists($image->path)) {
                $filename = basename($image->path);
                $subDir = dirname($image->path);
                $targetDir = $imagesDir.'/'.$subDir;
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                file_put_contents($targetDir.'/'.$filename, $disk->get($image->path));
            }
        }

        // Also export avatar if it exists
        if ($user->avatar_path && $user->avatar_disk) {
            $avatarDisk = Storage::disk($user->avatar_disk);
            if ($avatarDisk->exists($user->avatar_path)) {
                $avatarDir = $tempDir.'/avatar';
                mkdir($avatarDir, 0755, true);
                file_put_contents($avatarDir.'/'.basename($user->avatar_path), $avatarDisk->get($user->avatar_path));
                $data['user_settings']['avatar_filename'] = basename($user->avatar_path);
                // Re-write JSON with avatar info
                file_put_contents($tempDir.'/data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $zipPath = storage_path('app/temp/constellation_export_'.$userId.'_'.time().'.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addDirectoryToZip($zip, $tempDir, '');

        $zip->close();

        // Clean up temp directory
        $this->deleteDirectory($tempDir);

        return $zipPath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $prefix.substr($filePath, strlen($directory) + 1);
            $zip->addFile($filePath, $relativePath);
        }
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

    private function enumValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && property_exists($value, 'value') && is_string($value->value)) {
            return $value->value;
        }

        return null;
    }
}
