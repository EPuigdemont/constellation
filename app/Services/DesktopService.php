<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\User;
use Illuminate\Support\Str;

class DesktopService
{
    /**
     * Load all entity cards for a user's desktop.
     *
     * Includes the user's own entities and public entities from others.
     * Left-joins entity_positions so every card has coordinates.
     *
     * @return array<int, array{id: string, type: string, title: string, preview: string, mood: string|null, color_override: string|null, is_public: bool, x: float, y: float, z_index: int, owner_id: int}>
     */
    public function loadCards(User $user): array
    {
        $cards = [];

        $entityTypes = [
            'diary_entry' => DiaryEntry::class,
            'note' => Note::class,
            'postit' => Postit::class,
            'image' => Image::class,
        ];

        foreach ($entityTypes as $morphType => $modelClass) {
            $entities = $modelClass::query()
                ->where('user_id', $user->id)
                ->orWhere('is_public', true)
                ->get();

            foreach ($entities as $entity) {
                $position = EntityPosition::query()
                    ->where('user_id', $user->id)
                    ->where('entity_id', $entity->id)
                    ->where('entity_type', $morphType)
                    ->first();

                $cards[] = $this->normalizeCard($entity, $morphType, $position);
            }
        }

        return $cards;
    }

    /**
     * Save or update the position of an entity on a user's desktop.
     */
    public function savePosition(User $user, string $entityId, string $entityType, float $x, float $y, int $zIndex): void
    {
        EntityPosition::updateOrCreate(
            [
                'user_id' => $user->id,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
            ],
            [
                'x' => $x,
                'y' => $y,
                'z_index' => $zIndex,
            ],
        );
    }

    /**
     * Get the next z_index for bringing a card to front.
     */
    public function nextZIndex(User $user): int
    {
        $max = EntityPosition::where('user_id', $user->id)->max('z_index');

        return ($max ?? 0) + 1;
    }

    /**
     * Assign a default staggered position for a new entity on the desktop.
     * Places entity near the provided viewport center with a small random offset to avoid stacking.
     */
    public function assignDefaultPosition(User $user, string $entityId, string $entityType, float $centerX = 2000.0, float $centerY = 2000.0): EntityPosition
    {
        $offsetX = random_int(-80, 80);
        $offsetY = random_int(-60, 60);

        $x = max(0.0, min(3800.0, $centerX + $offsetX));
        $y = max(0.0, min(3800.0, $centerY + $offsetY));

        return EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'x' => $x,
            'y' => $y,
            'z_index' => $this->nextZIndex($user),
        ]);
    }

    /**
     * Persist the user's zoom level.
     */
    public function saveZoom(User $user, float $zoom): void
    {
        $user->update(['desktop_zoom' => $zoom]);
    }

    /**
     * Normalize an entity model into a card array for the frontend.
     */
    private function normalizeCard(object $entity, string $type, ?EntityPosition $position): array
    {
        $title = match ($type) {
            'diary_entry' => $entity->title ?? '',
            'note' => $entity->title ?? '',
            'postit' => '',
            'image' => $entity->alt ?? '',
        };

        $preview = match ($type) {
            'diary_entry', 'note', 'postit' => Str::limit(strip_tags($entity->body ?? ''), 120),
            'image' => $entity->alt ?? '',
        };

        $mood = $entity->mood?->value ?? null;

        return [
            'id' => $entity->id,
            'type' => $type,
            'title' => $title,
            'preview' => $preview,
            'mood' => $mood,
            'color_override' => $entity->color_override ?? null,
            'is_public' => (bool) $entity->is_public,
            'x' => $position?->x ?? 0.0,
            'y' => $position?->y ?? 0.0,
            'z_index' => $position?->z_index ?? 0,
            'owner_id' => $entity->user_id,
            'created_at' => $entity->created_at?->toIso8601String(),
            'updated_at' => $entity->updated_at?->toIso8601String(),
        ];
    }
}
