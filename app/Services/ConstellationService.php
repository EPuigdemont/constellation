<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\EntityRelationship;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Collection;

class ConstellationService
{
    /**
     * Build the full graph data (nodes + edges) for a user.
     *
     * @return array{nodes: list<array>, edges: list<array>}
     */
    public function buildGraph(User $user, array $filters = []): array
    {
        $entities = $this->loadEntities($user, $filters);
        $nodes = $this->buildNodes($entities);
        $relationships = $this->loadRelationships($user, $entities);
        $tagEdges = $this->computeTagEdges($entities);
        $dateEdges = $this->computeDateEdges($entities);

        $edges = $this->mergeEdges($relationships, $tagEdges, $dateEdges);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * Load all entities for a user, applying optional filters.
     */
    private function loadEntities(User $user, array $filters): Collection
    {
        $entities = collect();

        $typeFilter = $filters['type'] ?? 'all';
        $tagFilter = $filters['tag'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $models = [
            'diary' => DiaryEntry::class,
            'note' => Note::class,
            'postit' => Postit::class,
            'image' => Image::class,
            'reminder' => Reminder::class,
        ];

        foreach ($models as $type => $modelClass) {
            if ($typeFilter !== 'all' && $typeFilter !== $type) {
                continue;
            }

            $query = $modelClass::where('user_id', $user->id)->with('tags');

            if ($tagFilter) {
                $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagFilter));
            }

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            }

            $query->get()->each(function ($entity) use ($entities, $type) {
                $entities->push([
                    'model' => $entity,
                    'type' => $type,
                    'id' => $entity->id,
                ]);
            });
        }

        return $entities;
    }

    /**
     * Convert entities to node data for D3.
     *
     * @return list<array>
     */
    private function buildNodes(Collection $entities): array
    {
        return $entities->map(function (array $item) {
            $entity = $item['model'];
            $type = $item['type'];

            $title = match ($type) {
                'postit' => 'Post-it',
                'image' => $entity->title ?: $entity->alt ?: 'Image',
                'reminder' => $entity->title ?: 'Reminder',
                default => $entity->title ?: 'Untitled',
            };

            return [
                'id' => $entity->id,
                'type' => $type,
                'title' => $title,
                'preview' => str(strip_tags($entity->body ?? ''))->limit(120)->toString(),
                'mood' => $this->enumValue($entity->mood) ?? 'summer',
                'color_override' => $entity->color_override,
                'tags' => $entity->tags->pluck('name')->all(),
                'created_at' => $entity->created_at->toIso8601String(),
                'day_of_week' => $entity->created_at->dayOfWeekIso,
                'month' => $entity->created_at->month,
            ];
        })->values()->all();
    }

    /**
     * Load explicit entity relationships (parent_child, sibling).
     *
     * @return list<array>
     */
    private function loadRelationships(User $user, Collection $entities): array
    {
        $entityIds = $entities->pluck('id')->all();

        if (empty($entityIds)) {
            return [];
        }

        return EntityRelationship::query()
            ->whereIn('entity_a_id', $entityIds)
            ->whereIn('entity_b_id', $entityIds)
            ->get()
            ->map(fn (EntityRelationship $rel): array => [
                'source' => $rel->entity_a_id,
                'target' => $rel->entity_b_id,
                'type' => $this->enumValue($rel->relationship_type) ?? '',
                'strength' => 1.0,
            ])
            ->all();
    }

    /**
     * Compute implicit edges between entities that share tags.
     * Strength is proportional to the number of shared tags.
     *
     * @return list<array>
     */
    private function computeTagEdges(Collection $entities): array
    {
        $edges = [];
        $tagMap = [];

        foreach ($entities as $item) {
            $entityId = $item['id'];
            $tagIds = $item['model']->tags->pluck('id')->all();
            $tagMap[$entityId] = $tagIds;
        }

        $ids = array_keys($tagMap);
        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $shared = array_intersect($tagMap[$ids[$i]], $tagMap[$ids[$j]]);
                if (! empty($shared)) {
                    $edges[] = [
                        'source' => $ids[$i],
                        'target' => $ids[$j],
                        'type' => 'tag',
                        'strength' => min(count($shared) * 0.3, 1.0),
                    ];
                }
            }
        }

        return $edges;
    }

    /**
     * Compute implicit edges between entities created on the same day.
     *
     * @return list<array>
     */
    private function computeDateEdges(Collection $entities): array
    {
        $edges = [];
        $dateMap = [];

        foreach ($entities as $item) {
            $date = $item['model']->created_at->toDateString();
            $dateMap[$date][] = $item['id'];
        }

        foreach ($dateMap as $ids) {
            $count = count($ids);
            if ($count < 2) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $edges[] = [
                        'source' => $ids[$i],
                        'target' => $ids[$j],
                        'type' => 'date',
                        'strength' => 0.2,
                    ];
                }
            }
        }

        return $edges;
    }

    /**
     * Merge all edge sources, deduplicating and keeping strongest.
     *
     * @return list<array>
     */
    private function mergeEdges(array ...$edgeSets): array
    {
        $map = [];

        foreach ($edgeSets as $edges) {
            foreach ($edges as $edge) {
                $key = $this->edgeKey($edge['source'], $edge['target']);

                if (! isset($map[$key]) || $edge['strength'] > $map[$key]['strength']) {
                    // Prefer explicit relationship types over implicit
                    if (isset($map[$key]) && in_array($map[$key]['type'], ['parent_child', 'sibling']) && ! in_array($edge['type'], ['parent_child', 'sibling'])) {
                        continue;
                    }
                    $map[$key] = $edge;
                }
            }
        }

        return array_values($map);
    }

    private function edgeKey(string $a, string $b): string
    {
        return $a < $b ? "{$a}:{$b}" : "{$b}:{$a}";
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
