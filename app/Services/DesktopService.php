<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RelationshipType;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\EntityRelationship;
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
    /**
     * Load all entity cards for a user's desktop.
     *
     * @param  array<string, class-string>|null  $entityTypes  Override which entity types to load
     */
    public function loadCards(User $user, string $context = 'desktop', ?array $entityTypes = null): array
    {
        $cards = [];

        $entityTypes ??= [
            'diary_entry' => DiaryEntry::class,
            'note' => Note::class,
            'postit' => Postit::class,
            'image' => Image::class,
        ];

        // Pre-load all relationships for efficient lookups
        $relationships = EntityRelationship::all();

        foreach ($entityTypes as $morphType => $modelClass) {
            $query = $modelClass::query()
                ->where('user_id', $user->id)
                ->orWhere('is_public', true);

            if (method_exists($modelClass, 'tags')) {
                $query->with('tags');
            }

            $entities = $query->get();

            foreach ($entities as $entity) {
                $position = EntityPosition::query()
                    ->where('user_id', $user->id)
                    ->where('entity_id', $entity->id)
                    ->where('entity_type', $morphType)
                    ->where('context', $context)
                    ->first();

                $cards[] = $this->normalizeCard($entity, $morphType, $position, $relationships);
            }
        }

        return $cards;
    }

    /**
     * Save or update the position of an entity on a user's desktop.
     */
    public function savePosition(User $user, string $entityId, string $entityType, float $x, float $y, int $zIndex, string $context = 'desktop'): void
    {
        EntityPosition::updateOrCreate(
            [
                'user_id' => $user->id,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'context' => $context,
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
    public function nextZIndex(User $user, string $context = 'desktop'): int
    {
        $max = EntityPosition::where('user_id', $user->id)
            ->where('context', $context)
            ->max('z_index');

        return ($max ?? 0) + 1;
    }

    /**
     * Assign a default staggered position for a new entity on the desktop.
     * Places entity near the provided viewport center with a small random offset to avoid stacking.
     */
    public function assignDefaultPosition(User $user, string $entityId, string $entityType, float $centerX = 2000.0, float $centerY = 2000.0, string $context = 'desktop'): EntityPosition
    {
        $offsetX = random_int(-80, 80);
        $offsetY = random_int(-60, 60);

        $x = max(0.0, min(3800.0, $centerX + $offsetX));
        $y = max(0.0, min(3800.0, $centerY + $offsetY));

        return EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'context' => $context,
            'x' => $x,
            'y' => $y,
            'z_index' => $this->nextZIndex($user, $context),
        ]);
    }

    /**
     * Save or update the custom size of an entity on a user's desktop.
     */
    public function saveSize(User $user, string $entityId, string $entityType, float $width, float $height, string $context = 'desktop'): void
    {
        EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->where('context', $context)
            ->update([
                'width' => $width,
                'height' => $height,
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
     * Create or find a parent-child relationship (post-it attached to a parent entity).
     */
    public function attachToParent(string $childId, string $childType, string $parentId, string $parentType): EntityRelationship
    {
        // Remove any existing parent relationship for this child
        $this->detachFromParent($childId, $childType);

        return EntityRelationship::create([
            'entity_a_id' => $parentId,
            'entity_a_type' => $parentType,
            'entity_b_id' => $childId,
            'entity_b_type' => $childType,
            'relationship_type' => RelationshipType::ParentChild,
        ]);
    }

    /**
     * Remove parent-child relationship for an entity (detach from parent).
     */
    public function detachFromParent(string $childId, string $childType): void
    {
        EntityRelationship::query()
            ->where('entity_b_id', $childId)
            ->where('entity_b_type', $childType)
            ->where('relationship_type', RelationshipType::ParentChild)
            ->delete();
    }

    /**
     * Create a sibling relationship between two entities.
     */
    public function linkSiblings(string $entityAId, string $entityAType, string $entityBId, string $entityBType): EntityRelationship
    {
        // Avoid duplicates
        $existing = EntityRelationship::query()
            ->where('relationship_type', RelationshipType::Sibling)
            ->where(function ($q) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                $q->where(function ($q2) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                    $q2->where('entity_a_id', $entityAId)
                        ->where('entity_a_type', $entityAType)
                        ->where('entity_b_id', $entityBId)
                        ->where('entity_b_type', $entityBType);
                })->orWhere(function ($q2) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                    $q2->where('entity_a_id', $entityBId)
                        ->where('entity_a_type', $entityBType)
                        ->where('entity_b_id', $entityAId)
                        ->where('entity_b_type', $entityAType);
                });
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return EntityRelationship::create([
            'entity_a_id' => $entityAId,
            'entity_a_type' => $entityAType,
            'entity_b_id' => $entityBId,
            'entity_b_type' => $entityBType,
            'relationship_type' => RelationshipType::Sibling,
        ]);
    }

    /**
     * Remove a sibling relationship between two entities.
     */
    public function unlinkSiblings(string $entityAId, string $entityAType, string $entityBId, string $entityBType): void
    {
        EntityRelationship::query()
            ->where('relationship_type', RelationshipType::Sibling)
            ->where(function ($q) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                $q->where(function ($q2) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                    $q2->where('entity_a_id', $entityAId)
                        ->where('entity_a_type', $entityAType)
                        ->where('entity_b_id', $entityBId)
                        ->where('entity_b_type', $entityBType);
                })->orWhere(function ($q2) use ($entityAId, $entityAType, $entityBId, $entityBType): void {
                    $q2->where('entity_a_id', $entityBId)
                        ->where('entity_a_type', $entityBType)
                        ->where('entity_b_id', $entityAId)
                        ->where('entity_b_type', $entityAType);
                });
            })
            ->delete();
    }

    /**
     * Get relationship counts and parent info for card enrichment.
     *
     * @return array{parent_id: string|null, parent_type: string|null, children_count: int, siblings_count: int}
     */
    public function getRelationshipData(string $entityId, string $entityType, $relationships): array
    {
        // Find parent (this entity is entity_b in a parent_child relationship)
        $parentRel = $relationships->first(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $rel->relationship_type === RelationshipType::ParentChild
                && $rel->entity_b_id === $entityId
                && $rel->entity_b_type === $entityType;
        });

        // Count children (this entity is entity_a in parent_child relationships)
        $childrenCount = $relationships->filter(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $rel->relationship_type === RelationshipType::ParentChild
                && $rel->entity_a_id === $entityId
                && $rel->entity_a_type === $entityType;
        })->count();

        // Count siblings (this entity appears as either side in sibling relationships)
        $siblingsCount = $relationships->filter(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $rel->relationship_type === RelationshipType::Sibling
                && (
                    ($rel->entity_a_id === $entityId && $rel->entity_a_type === $entityType)
                    || ($rel->entity_b_id === $entityId && $rel->entity_b_type === $entityType)
                );
        })->count();

        return [
            'parent_id' => $parentRel?->entity_a_id,
            'parent_type' => $parentRel?->entity_a_type,
            'children_count' => $childrenCount,
            'siblings_count' => $siblingsCount,
        ];
    }

    /**
     * Normalize an entity model into a card array for the frontend.
     */
    private function normalizeCard(object $entity, string $type, ?EntityPosition $position, $relationships = null): array
    {
        $title = match ($type) {
            'diary_entry' => $entity->title ?? '',
            'note' => $entity->title ?? '',
            'postit' => '',
            'image' => $entity->title ?? $entity->alt ?? '',
        };

        $preview = match ($type) {
            'diary_entry', 'note', 'postit' => Str::limit(strip_tags($entity->body ?? ''), 120),
            'image' => $entity->alt ?? '',
        };

        $mood = $entity->mood?->value ?? null;

        $relData = $relationships
            ? $this->getRelationshipData($entity->id, $type, $relationships)
            : ['parent_id' => null, 'parent_type' => null, 'children_count' => 0, 'siblings_count' => 0];

        $tagIds = method_exists($entity, 'tags') && $entity->relationLoaded('tags')
            ? $entity->tags->pluck('id')->all()
            : [];

        $imageUrl = $type === 'image' ? route('images.serve', $entity) : null;

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
            'width' => $position?->width,
            'height' => $position?->height,
            'owner_id' => $entity->user_id,
            'created_at' => $entity->created_at?->toIso8601String(),
            'updated_at' => $entity->updated_at?->toIso8601String(),
            'parent_id' => $relData['parent_id'],
            'parent_type' => $relData['parent_type'],
            'children_count' => $relData['children_count'],
            'siblings_count' => $relData['siblings_count'],
            'tag_ids' => $tagIds,
            'image_url' => $imageUrl,
            'is_hidden' => (bool) ($position?->is_hidden ?? false),
        ];
    }
}
