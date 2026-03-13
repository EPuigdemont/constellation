<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Tag;
use App\Services\DesktopService;
use App\Services\EditorImageService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Canvas')]
class Canvas extends Component
{
    use WithFileUploads;

    public float $zoom = 1.0;

    /** @var array<int, array<string, mixed>> */
    public array $cards = [];

    public int $maxZIndex = 0;

    public bool $showEditorModal = false;

    public string $editorMode = 'diary';

    public string $editingEntityId = '';

    public string $editorTitle = '';

    public string $editorBody = '';

    public string $editorMood = 'plain';

    public ?string $editorColorOverride = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $editorImage = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $imageUpload = null;

    /** @var array<int, string> */
    public array $editorTagIds = [];

    public string $tagSearch = '';

    /** @var array<int, array{id: string, name: string, color: string|null}> */
    public array $availableTags = [];

    public float $viewportCenterX = 2000.0;

    public float $viewportCenterY = 2000.0;

    /** Linking mode: 'attach' to attach post-it to parent, 'sibling' to link siblings */
    public string $linkingMode = '';

    /** The first entity selected for linking */
    public string $linkingEntityId = '';

    public string $linkingEntityType = '';

    /** @var array<int, string> */
    public array $filterTags = [];

    /** @var array<int, array{id: string, name: string}> */
    public array $filterAvailableTags = [];

    public function mount(DesktopService $service): void
    {
        $user = Auth::user();
        $this->zoom = $user->desktop_zoom ?? 1.0;
        $this->cards = $service->loadCards($user);
        $this->maxZIndex = $service->nextZIndex($user) - 1;

        $this->filterAvailableTags = Tag::forUser($user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name])
            ->all();
    }

    public function savePosition(DesktopService $service, string $entityId, string $entityType, float $x, float $y, int $zIndex): void
    {
        $service->savePosition(Auth::user(), $entityId, $entityType, $x, $y, $zIndex);
    }

    public function bringToFront(DesktopService $service, string $entityId, string $entityType): int
    {
        $user = Auth::user();
        $newZ = $service->nextZIndex($user);

        \App\Models\EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->where('context', 'desktop')
            ->update(['z_index' => $newZ]);

        $this->maxZIndex = $newZ;

        return $newZ;
    }

    public function saveSize(DesktopService $service, string $entityId, string $entityType, float $width, float $height): void
    {
        $service->saveSize(Auth::user(), $entityId, $entityType, $width, $height);
    }

    public function saveZoom(DesktopService $service, float $zoom): void
    {
        $this->zoom = $zoom;
        $service->saveZoom(Auth::user(), $zoom);
    }

    public function openDiaryModal(float $centerX = 2000.0, float $centerY = 2000.0): void
    {
        $this->resetEditor();
        $this->editorMode = 'diary';
        $this->viewportCenterX = $centerX;
        $this->viewportCenterY = $centerY;
        $this->loadTagsForEditor();
        $this->showEditorModal = true;
    }

    public function openNoteModal(float $centerX = 2000.0, float $centerY = 2000.0): void
    {
        $this->resetEditor();
        $this->editorMode = 'note';
        $this->viewportCenterX = $centerX;
        $this->viewportCenterY = $centerY;
        $this->loadTagsForEditor();
        $this->showEditorModal = true;
    }

    public function createPostit(DesktopService $service, float $centerX = 2000.0, float $centerY = 2000.0): void
    {
        $user = Auth::user();

        $postit = Postit::create([
            'user_id' => $user->id,
            'body' => '',
            'mood' => Mood::Summer,
            'is_public' => false,
        ]);

        $position = $service->assignDefaultPosition($user, $postit->id, 'postit', $centerX, $centerY);

        $card = [
            'id' => $postit->id,
            'type' => 'postit',
            'title' => '',
            'preview' => '',
            'mood' => 'summer',
            'color_override' => null,
            'is_public' => false,
            'x' => $position->x,
            'y' => $position->y,
            'z_index' => $position->z_index,
            'owner_id' => $user->id,
            'created_at' => $postit->created_at->toIso8601String(),
            'updated_at' => $postit->updated_at->toIso8601String(),
            'parent_id' => null,
            'parent_type' => null,
            'children_count' => 0,
            'siblings_count' => 0,
            'width' => null,
            'height' => null,
            'tag_ids' => [],
        ];

        $this->cards[] = $card;
        $this->maxZIndex = $position->z_index;

        $this->dispatch('card-created', card: array_merge($card, ['is_owner' => true]));
    }

    public function uploadImage(EditorImageService $imageService, DesktopService $service): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('[Canvas] uploadImage called', [
                'hasFile' => $this->imageUpload !== null,
                'fileClass' => $this->imageUpload ? get_class($this->imageUpload) : null,
                'fileName' => $this->imageUpload?->getClientOriginalName(),
                'fileSize' => $this->imageUpload?->getSize(),
                'fileMime' => $this->imageUpload?->getMimeType(),
            ]);

            if (! $this->imageUpload) {
                \Illuminate\Support\Facades\Log::warning('[Canvas] uploadImage: imageUpload is null, aborting');

                return;
            }

            $user = Auth::user();
            $image = $imageService->store($user, $this->imageUpload);
            \Illuminate\Support\Facades\Log::info('[Canvas] Image stored', ['imageId' => $image->id, 'path' => $image->path]);

            $position = $service->assignDefaultPosition($user, $image->id, 'image');
            \Illuminate\Support\Facades\Log::info('[Canvas] Position assigned', ['positionId' => $position->id, 'x' => $position->x, 'y' => $position->y]);

            $card = [
                'id' => $image->id,
                'type' => 'image',
                'title' => $image->alt ?? '',
                'preview' => $image->alt ?? '',
                'mood' => null,
                'color_override' => null,
                'is_public' => false,
                'x' => $position->x,
                'y' => $position->y,
                'z_index' => $position->z_index,
                'owner_id' => $user->id,
                'created_at' => $image->created_at->toIso8601String(),
                'updated_at' => $image->updated_at->toIso8601String(),
                'parent_id' => null,
                'parent_type' => null,
                'children_count' => 0,
                'siblings_count' => 0,
                'width' => null,
                'height' => null,
                'tag_ids' => [],
                'image_url' => route('images.serve', $image),
            ];

            $this->cards[] = $card;
            $this->maxZIndex = $position->z_index;
            $this->imageUpload = null;

            $this->dispatch('card-created', card: array_merge($card, ['is_owner' => true]));
            \Illuminate\Support\Facades\Log::info('[Canvas] uploadImage completed successfully', ['cardId' => $card['id']]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Canvas] uploadImage failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function saveEditor(DesktopService $service): void
    {
        $user = Auth::user();
        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;

        if ($mood !== Mood::Custom) {
            $this->editorColorOverride = null;
        }

        if ($this->editingEntityId !== '') {
            $this->updateExistingEntity($user, $mood);
        } else {
            $this->createNewEntity($user, $mood, $service);
        }

        $this->showEditorModal = false;
        $this->resetEditor();
    }

    public function openEditModal(string $entityId, string $entityType): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $this->resetEditor();
        $this->editingEntityId = $entityId;
        $this->editorMode = $entityType === 'diary_entry' ? 'diary' : $entityType;
        $this->editorTitle = $model->title ?? '';
        $this->editorBody = $model->body ?? '';
        $this->editorMood = $model->mood?->value ?? 'plain';
        $this->editorColorOverride = $model->color_override ?? null;
        $this->loadTagsForEditor($model);
        $this->showEditorModal = true;
    }

    public function uploadEditorImage(EditorImageService $service): void
    {
        if (! $this->editorImage) {
            return;
        }

        $user = Auth::user();
        $image = $service->store($user, $this->editorImage);
        $url = route('images.serve', $image);

        $this->editorImage = null;

        $this->dispatch('editor-image-uploaded', url: $url);
    }

    public function autosaveEditor(): void
    {
        if ($this->editingEntityId === '') {
            return;
        }

        $card = collect($this->cards)->firstWhere('id', $this->editingEntityId);
        if (! $card) {
            return;
        }

        $model = $this->resolveEntity($this->editingEntityId, $card['type']);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;
        $data = ['body' => $this->editorBody, 'mood' => $mood, 'color_override' => $this->editorColorOverride];

        if (in_array($card['type'], ['diary_entry', 'note'], true)) {
            $data['title'] = $this->editorTitle;
        }

        $model->update($data);

        if (method_exists($model, 'tags')) {
            $model->tags()->sync($this->editorTagIds);
        }

        $this->updateCardInList($this->editingEntityId, [
            'title' => $this->editorTitle,
            'preview' => \Illuminate\Support\Str::limit(strip_tags($this->editorBody), 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
        ]);
    }

    public function loadTagsForEditor(?object $model = null): void
    {
        $user = Auth::user();
        $this->availableTags = Tag::forUser($user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])
            ->all();

        if ($model && method_exists($model, 'tags')) {
            $this->editorTagIds = $model->tags()->pluck('tags.id')->all();
        }
    }

    public function toggleTag(string $tagId): void
    {
        if (in_array($tagId, $this->editorTagIds, true)) {
            $this->editorTagIds = array_values(array_filter(
                $this->editorTagIds,
                fn (string $id): bool => $id !== $tagId,
            ));
        } else {
            $this->editorTagIds[] = $tagId;
        }
    }

    public function createTagInline(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $user = Auth::user();
        $tag = Tag::create([
            'name' => $name,
            'user_id' => $user->id,
        ]);

        $this->availableTags[] = [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ];

        $this->editorTagIds[] = $tag->id;
        $this->tagSearch = '';
    }

    public function deleteFromEditor(): void
    {
        if ($this->editingEntityId === '') {
            return;
        }

        $card = collect($this->cards)->firstWhere('id', $this->editingEntityId);
        if (! $card) {
            return;
        }

        $this->deleteEntity($this->editingEntityId, $card['type']);
        $this->showEditorModal = false;
        $this->resetEditor();
    }

    public function deleteEntity(string $entityId, string $entityType): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('delete', $model);

        $model->delete();

        $this->cards = array_values(
            array_filter($this->cards, fn (array $card): bool => $card['id'] !== $entityId),
        );

        $this->dispatch('card-deleted', entityId: $entityId);
    }

    public function quickSavePostit(string $entityId, string $body): void
    {
        $model = $this->resolveEntity($entityId, 'postit');
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $model->update(['body' => $body]);

        $this->updateCardInList($entityId, [
            'preview' => \Illuminate\Support\Str::limit(strip_tags($body), 120),
        ]);
    }

    public function changeMood(string $entityId, string $entityType, string $mood): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $moodEnum = Mood::tryFrom($mood);
        if (! $moodEnum) {
            return;
        }

        $model->update(['mood' => $moodEnum]);

        $this->updateCardInList($entityId, ['mood' => $mood]);
    }

    /**
     * Start linking mode — waiting for user to select a second entity.
     */
    public function startLinking(string $entityId, string $entityType, string $mode): void
    {
        $this->linkingMode = $mode;
        $this->linkingEntityId = $entityId;
        $this->linkingEntityType = $entityType;

        $this->dispatch('linking-started', mode: $mode, entityId: $entityId);
    }

    /**
     * Cancel linking mode.
     */
    public function cancelLinking(): void
    {
        $this->linkingMode = '';
        $this->linkingEntityId = '';
        $this->linkingEntityType = '';

        $this->dispatch('linking-cancelled');
    }

    /**
     * Complete linking: attach post-it to parent or create sibling link.
     */
    public function completeLinking(DesktopService $service, string $targetEntityId, string $targetEntityType): void
    {
        if ($this->linkingMode === '' || $this->linkingEntityId === '') {
            return;
        }

        $sourceModel = $this->resolveEntity($this->linkingEntityId, $this->linkingEntityType);
        $targetModel = $this->resolveEntity($targetEntityId, $targetEntityType);

        if (! $sourceModel || ! $targetModel) {
            $this->cancelLinking();
            return;
        }

        Gate::authorize('update', $sourceModel);

        if ($this->linkingMode === 'attach') {
            // Attach: source (post-it) becomes child of target (parent)
            $service->attachToParent(
                $this->linkingEntityId,
                $this->linkingEntityType,
                $targetEntityId,
                $targetEntityType,
            );

            $this->updateCardInList($this->linkingEntityId, [
                'parent_id' => $targetEntityId,
                'parent_type' => $targetEntityType,
            ]);

            // Increment children count on target
            foreach ($this->cards as $i => $card) {
                if ($card['id'] === $targetEntityId) {
                    $this->cards[$i]['children_count'] = ($card['children_count'] ?? 0) + 1;
                    break;
                }
            }

            $this->dispatch('card-attached', childId: $this->linkingEntityId, parentId: $targetEntityId);
        } elseif ($this->linkingMode === 'sibling') {
            // Don't link entity to itself
            if ($this->linkingEntityId === $targetEntityId) {
                $this->cancelLinking();
                return;
            }

            $service->linkSiblings(
                $this->linkingEntityId,
                $this->linkingEntityType,
                $targetEntityId,
                $targetEntityType,
            );

            // Increment siblings count on both
            foreach ($this->cards as $i => $card) {
                if ($card['id'] === $this->linkingEntityId || $card['id'] === $targetEntityId) {
                    $this->cards[$i]['siblings_count'] = ($card['siblings_count'] ?? 0) + 1;
                }
            }

            $this->dispatch('card-linked', entityAId: $this->linkingEntityId, entityBId: $targetEntityId);
        }

        $this->cancelLinking();
    }

    /**
     * Detach a post-it from its parent.
     */
    public function detachFromParent(DesktopService $service, string $entityId, string $entityType): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        // Find current parent before detaching
        $card = collect($this->cards)->firstWhere('id', $entityId);
        $parentId = $card['parent_id'] ?? null;

        $service->detachFromParent($entityId, $entityType);

        $this->updateCardInList($entityId, [
            'parent_id' => null,
            'parent_type' => null,
        ]);

        // Decrement children count on former parent
        if ($parentId) {
            foreach ($this->cards as $i => $c) {
                if ($c['id'] === $parentId) {
                    $this->cards[$i]['children_count'] = max(0, ($c['children_count'] ?? 0) - 1);
                    break;
                }
            }
        }

        $this->dispatch('card-detached', entityId: $entityId, parentId: $parentId);
    }

    public function togglePublic(string $entityId, string $entityType): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $model->update(['is_public' => ! $model->is_public]);

        $this->updateCardInList($entityId, ['is_public' => ! $model->is_public]);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.desktop');
    }

    private function resetEditor(): void
    {
        $this->editingEntityId = '';
        $this->editorTitle = '';
        $this->editorBody = '';
        $this->editorMood = 'plain';
        $this->editorColorOverride = null;
        $this->editorImage = null;
        $this->imageUpload = null;
        $this->editorTagIds = [];
        $this->tagSearch = '';
        $this->availableTags = [];
    }

    private function resolveEntity(string $entityId, string $entityType): ?object
    {
        $morphMap = Relation::morphMap();
        $class = $morphMap[$entityType] ?? null;

        if (! $class) {
            return null;
        }

        return $class::find($entityId);
    }

    private function createNewEntity(object $user, Mood $mood, DesktopService $service): void
    {
        $data = [
            'user_id' => $user->id,
            'title' => $this->editorTitle,
            'body' => $this->editorBody,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
            'is_public' => false,
        ];

        if ($this->editorMode === 'diary') {
            $entity = DiaryEntry::create($data);
            $type = 'diary_entry';
        } else {
            $entity = Note::create($data);
            $type = 'note';
        }

        if (method_exists($entity, 'tags') && ! empty($this->editorTagIds)) {
            $entity->tags()->sync($this->editorTagIds);
        }

        $position = $service->assignDefaultPosition($user, $entity->id, $type, $this->viewportCenterX, $this->viewportCenterY);

        $card = [
            'id' => $entity->id,
            'type' => $type,
            'title' => $entity->title ?? '',
            'preview' => \Illuminate\Support\Str::limit(strip_tags($entity->body ?? ''), 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
            'is_public' => false,
            'x' => $position->x,
            'y' => $position->y,
            'z_index' => $position->z_index,
            'owner_id' => $user->id,
            'created_at' => $entity->created_at->toIso8601String(),
            'updated_at' => $entity->updated_at->toIso8601String(),
            'parent_id' => null,
            'parent_type' => null,
            'children_count' => 0,
            'siblings_count' => 0,
            'width' => null,
            'height' => null,
            'tag_ids' => $this->editorTagIds,
            'image_url' => null,
        ];

        $this->cards[] = $card;
        $this->maxZIndex = $position->z_index;

        $this->dispatch('card-created', card: array_merge($card, ['is_owner' => true]));
    }

    private function updateExistingEntity(object $user, Mood $mood): void
    {
        $card = collect($this->cards)->firstWhere('id', $this->editingEntityId);
        if (! $card) {
            return;
        }

        $model = $this->resolveEntity($this->editingEntityId, $card['type']);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $data = [
            'body' => $this->editorBody,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
        ];

        if (in_array($card['type'], ['diary_entry', 'note'], true)) {
            $data['title'] = $this->editorTitle;
        }

        $model->update($data);

        if (method_exists($model, 'tags')) {
            $model->tags()->sync($this->editorTagIds);
        }

        $updates = [
            'title' => $this->editorTitle,
            'preview' => \Illuminate\Support\Str::limit(strip_tags($this->editorBody), 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
            'tag_ids' => $this->editorTagIds,
        ];

        $this->updateCardInList($this->editingEntityId, $updates);

        $this->dispatch('card-updated', entityId: $this->editingEntityId, updates: $updates);
    }

    private function updateCardInList(string $entityId, array $updates): void
    {
        foreach ($this->cards as $i => $card) {
            if ($card['id'] === $entityId) {
                $this->cards[$i] = array_merge($card, $updates);
                break;
            }
        }
    }
}
