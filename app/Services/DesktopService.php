<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RelationshipType;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\EntityRelationship;
use App\Models\EntityShare;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DesktopService
{
    private const int IMAGE_CARD_MIN_WIDTH = 224;

    private const int IMAGE_CARD_MIN_HEIGHT = 180;

    private const int IMAGE_PREVIEW_MIN_WIDTH = 180;

    private const int IMAGE_PREVIEW_MIN_HEIGHT = 120;

    private const int IMAGE_PREVIEW_MAX_WIDTH = 360;

    private const int IMAGE_PREVIEW_MAX_HEIGHT = 220;

    /**
     * Load all entity cards for a user's desktop.
     *
     * Includes the user's own entities and entities shared via EntityShare.
     * Left-joins entity_positions so every card has coordinates.
     *
     * @param  array<string, class-string>|null  $entityTypes  Override which entity types to load
     * @return list<array<string, mixed>>
     */
    public function loadCards(User $user, string $context = 'desktop', ?array $entityTypes = null): array
    {
        $cards = [];

        $entityTypes ??= [
            'diary_entry' => DiaryEntry::class,
            'note' => Note::class,
            'postit' => Postit::class,
            'image' => Image::class,
            'reminder' => Reminder::class,
        ];

        // Collect all entities first
        $allEntities = [];
        foreach ($entityTypes as $morphType => $modelClass) {
            // Get all entities owned by user
            $query = $modelClass::query()
                ->where('user_id', $user->id);

            $query->with('user');

            if (method_exists($modelClass, 'tags')) {
                $query->with('tags');
            }

            $entities = $query->get();

            foreach ($entities as $entity) {
                $allEntities[] = ['entity' => $entity, 'morphType' => $morphType];
            }
        }

        // Get entities shared with this user via EntityShare
        $sharedEntities = EntityShare::where('friend_id', $user->id)
            ->get()
            ->groupBy(function ($share) {
                return $share->entity_type;
            });

        foreach ($entityTypes as $morphType => $modelClass) {
            if (! isset($sharedEntities[$morphType])) {
                continue;
            }

            $entityIds = $sharedEntities[$morphType]->pluck('entity_id')->toArray();
            if (empty($entityIds)) {
                continue;
            }

            $query = $modelClass::query()
                ->whereIn('id', $entityIds);

            $query->with('user');

            if (method_exists($modelClass, 'tags')) {
                $query->with('tags');
            }

            $entities = $query->get();

            foreach ($entities as $entity) {
                $allEntities[] = ['entity' => $entity, 'morphType' => $morphType];
            }
        }

        $deduplicatedEntities = [];
        foreach ($allEntities as $item) {
            $entity = $item['entity'];
            $deduplicatedEntities[$item['morphType'].':'.$entity->id] = $item;
        }

        $allEntities = array_values($deduplicatedEntities);

        // Batch load all positions for this user+context instead of N+1 queries
        $entityIds = array_map(fn ($item) => $item['entity']->id, $allEntities);
        $positionRecords = EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->whereIn('entity_id', $entityIds)
            ->get();

        /** @var array<string, EntityPosition> $positions */
        $positions = [];
        foreach ($positionRecords as $position) {
            $positions[$position->entity_id.':'.$position->entity_type] = $position;
        }

        // Load only relationships involving the collected entity IDs
        $relationships = EntityRelationship::query()
            ->where(function ($q) use ($entityIds) {
                $q->whereIn('entity_a_id', $entityIds)
                    ->orWhereIn('entity_b_id', $entityIds);
            })
            ->get();

        foreach ($allEntities as $item) {
            $entity = $item['entity'];
            $morphType = $item['morphType'];
            $position = $positions[$entity->id.':'.$morphType] ?? null;

            $cards[] = $this->normalizeCard($entity, $morphType, $position, $relationships);
        }

        if ($user->isGuest() && isset($entityTypes['image'])) {
            $cards = array_merge(
                $cards,
                app(GuestDemoImageService::class)->cardsForContext($context),
            );
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

        if ($user->isGuest()) {
            $demoMax = collect(app(GuestDemoImageService::class)->cardsForContext($context))
                ->max('z_index');

            $max = max((int) ($max ?? 0), (int) ($demoMax ?? 0));
        }

        return ($max ?? 0) + 1;
    }

    /** @return array{width: int, height: int} */
    public function calculateDefaultImageCardSize(?int $imageWidth, ?int $imageHeight): array
    {
        $previewWidth = self::IMAGE_PREVIEW_MIN_WIDTH;
        $previewHeight = self::IMAGE_PREVIEW_MIN_HEIGHT;

        if (($imageWidth ?? 0) > 0 && ($imageHeight ?? 0) > 0) {
            $rawWidth = (float) $imageWidth;
            $rawHeight = (float) $imageHeight;

            $scale = min(
                self::IMAGE_PREVIEW_MAX_WIDTH / $rawWidth,
                self::IMAGE_PREVIEW_MAX_HEIGHT / $rawHeight,
                1.0,
            );

            $previewWidth = max(self::IMAGE_PREVIEW_MIN_WIDTH, (int) round($rawWidth * $scale));
            $previewHeight = max(self::IMAGE_PREVIEW_MIN_HEIGHT, (int) round($rawHeight * $scale));
        }

        $cardWidth = max(self::IMAGE_CARD_MIN_WIDTH, $previewWidth + 24);
        $cardHeight = max(self::IMAGE_CARD_MIN_HEIGHT, $previewHeight + 112);

        return ['width' => $cardWidth, 'height' => $cardHeight];
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
     * @param  Collection<int, EntityRelationship>  $relationships
     * @return array{parent_id: string|null, parent_type: string|null, children_count: int, siblings_count: int}
     */
    public function getRelationshipData(string $entityId, string $entityType, Collection $relationships): array
    {
        // Find parent (this entity is entity_b in a parent_child relationship)
        $parentRel = $relationships->first(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $this->relationshipTypeValue($rel->relationship_type) === RelationshipType::ParentChild->value
                && $rel->entity_b_id === $entityId
                && $rel->entity_b_type === $entityType;
        });

        // Count children (this entity is entity_a in parent_child relationships)
        $childrenCount = $relationships->filter(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $this->relationshipTypeValue($rel->relationship_type) === RelationshipType::ParentChild->value
                && $rel->entity_a_id === $entityId
                && $rel->entity_a_type === $entityType;
        })->count();

        // Count siblings (this entity appears as either side in sibling relationships)
        $siblingsCount = $relationships->filter(function (EntityRelationship $rel) use ($entityId, $entityType): bool {
            return $this->relationshipTypeValue($rel->relationship_type) === RelationshipType::Sibling->value
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
     *
     * @param  DiaryEntry|Note|Postit|Image|Reminder  $entity
     * @param  Collection<int, EntityRelationship>|null  $relationships
     * @return array<string, mixed>
     */
    private function normalizeCard(Model $entity, string $type, mixed $position, ?Collection $relationships = null): array
    {
        $position = $position instanceof EntityPosition ? $position : null;

        $imageWidth = $type === 'image' && $entity instanceof Image ? $entity->image_width : null;
        $imageHeight = $type === 'image' && $entity instanceof Image ? $entity->image_height : null;
        $defaultImageSize = $type === 'image'
            ? $this->calculateDefaultImageCardSize($imageWidth, $imageHeight)
            : ['width' => null, 'height' => null];
        $positionWidth = $position instanceof EntityPosition ? $position->width : null;
        $positionHeight = $position instanceof EntityPosition ? $position->height : null;

        $title = match ($type) {
            'diary_entry' => $entity->title ?? '',
            'note' => $entity->title ?? '',
            'postit' => '',
            'image' => $entity->title ?? $entity->alt ?? '',
            'reminder' => $entity->title ?? '',
            default => '',
        };

        $preview = match ($type) {
            'diary_entry', 'note', 'postit', 'reminder' => Str::limit(strip_tags($entity->body ?? ''), 120),
            'image' => $entity->alt ?? '',
            default => '',
        };

        $mood = $this->enumValue($entity->mood);

        $relData = $relationships
            ? $this->getRelationshipData($entity->id, $type, $relationships)
            : ['parent_id' => null, 'parent_type' => null, 'children_count' => 0, 'siblings_count' => 0];

        // All entity types in the union support tags relation
        /** @phpstan-ignore-next-line */
        $tagIds = ($entity instanceof DiaryEntry || $entity instanceof Note || $entity instanceof Postit || $entity instanceof Image || $entity instanceof Reminder)
            && method_exists($entity, 'tags') && $entity->relationLoaded('tags')
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
            'x' => $position->x ?? 0.0,
            'y' => $position->y ?? 0.0,
            'z_index' => $position->z_index ?? 0,
            'width' => $positionWidth ?? $defaultImageSize['width'],
            'height' => $positionHeight ?? $defaultImageSize['height'],
            'owner_id' => $entity->user_id,
            'owner_name' => $entity->user->name ?? '',
            'owner_username' => $entity->user->username ?? '',
            'created_at' => $entity->created_at?->toIso8601String(),
            'updated_at' => $entity->updated_at?->toIso8601String(),
            'parent_id' => $relData['parent_id'],
            'parent_type' => $relData['parent_type'],
            'children_count' => $relData['children_count'],
            'siblings_count' => $relData['siblings_count'],
            'tag_ids' => $tagIds,
            'image_url' => $imageUrl,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'is_hidden' => (bool) ($position->is_hidden ?? false),
        ];
    }

    private function relationshipTypeValue(mixed $type): ?string
    {
        return $type instanceof RelationshipType ? $type->value : (is_string($type) ? $type : null);
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
