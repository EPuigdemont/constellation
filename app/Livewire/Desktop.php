<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\User;
use App\Services\DesktopService;
use App\Services\EditorImageService;
use App\Services\LimitCheckerService;
use App\Services\ShareEntityService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Canvas')]
class Desktop extends Component
{
    use WithFileUploads;

    public float $zoom = 1.0;

    /** @var array<int, array<string, mixed>> */
    public array $cards = [];

    public int $maxZIndex = 0;

    public bool $showEditorModal = false;

    public bool $showReadonlyModal = false;

    public string $readonlyEntityType = '';

    public string $readonlyOwnerUsername = '';

    public string $readonlyTitle = '';

    public string $readonlyBody = '';

    public string $readonlyImageUrl = '';

    public string $readonlyUpdatedAt = '';

    public string $editorMode = 'diary';

    public string $editingEntityId = '';

    public string $editorTitle = '';

    public string $editorBody = '';

    public string $editorMood = 'plain';

    public ?string $editorColorOverride = null;

    /** @var TemporaryUploadedFile|null */
    public $editorImage = null;

    /** @var TemporaryUploadedFile|null */
    public $imageUpload = null;

    /** @var array<int, string> */
    public array $editorTagIds = [];

    public string $editorRemindAt = '';

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

    /** @var array<int, array{id: string, username: string, name: string}> */
    public array $userFriends = [];

    /** @var array<int, string> */
    public array $currentEntitySharedFriends = [];

    public string $limitError = '';

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

        EntityPosition::query()
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

        // Check limit before creating
        $limitChecker = app(LimitCheckerService::class);
        if (! $limitChecker->canCreateEntity($user, 'postit')) {
            $remaining = $limitChecker->getRemainingCount($user, 'postit');
            $this->limitError = "You have reached your post-it limit for today. Remaining: {$remaining}.";
            $this->dispatch('notify-error', message: $this->limitError);

            return;
        }

        $this->limitError = '';

        Gate::authorize('create', Postit::class);

        $postit = Postit::create([
            'user_id' => $user->id,
            'body' => '',
            'mood' => Mood::tryFrom($user->activeTheme()) ?? Mood::Summer,
        ]);

        $position = $service->assignDefaultPosition($user, $postit->id, 'postit', $centerX, $centerY);

        $card = [
            'id' => $postit->id,
            'type' => 'postit',
            'title' => '',
            'preview' => '',
            'mood' => $this->moodValue($postit->mood),
            'color_override' => null,
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

    public function createReminder(DesktopService $service, float $centerX = 2000.0, float $centerY = 2000.0): void
    {
        $user = Auth::user();

        // Check limit before creating
        $limitChecker = app(LimitCheckerService::class);
        if (! $limitChecker->canCreateEntity($user, 'reminder')) {
            $remaining = $limitChecker->getRemainingCount($user, 'reminder');
            $this->limitError = "You have reached your reminder limit for today. Remaining: {$remaining}.";
            $this->dispatch('notify-error', message: $this->limitError);

            return;
        }

        $this->limitError = '';

        Gate::authorize('create', Reminder::class);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => '',
            'body' => '',
            'remind_at' => now()->addDay(),
            'mood' => Mood::tryFrom($user->activeTheme()) ?? Mood::Summer,
        ]);

        $position = $service->assignDefaultPosition($user, $reminder->id, 'reminder', $centerX, $centerY);

        $card = [
            'id' => $reminder->id,
            'type' => 'reminder',
            'title' => '',
            'preview' => '',
            'mood' => $this->moodValue($reminder->mood),
            'color_override' => null,
            'x' => $position->x,
            'y' => $position->y,
            'z_index' => $position->z_index,
            'owner_id' => $user->id,
            'created_at' => $reminder->created_at->toIso8601String(),
            'updated_at' => $reminder->updated_at->toIso8601String(),
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

    public function updatedImageUpload(): void
    {
        $this->uploadImage(app(EditorImageService::class), app(DesktopService::class));
    }

    public function uploadImage(EditorImageService $imageService, DesktopService $service): void
    {
        try {
            Log::info('[Canvas] uploadImage called', [
                'hasFile' => $this->imageUpload !== null,
                'fileClass' => $this->imageUpload ? get_class($this->imageUpload) : null,
                'fileName' => $this->imageUpload?->getClientOriginalName(),
                'fileSize' => $this->imageUpload?->getSize(),
                'fileMime' => $this->imageUpload?->getMimeType(),
            ]);

            if (! $this->imageUpload) {
                Log::warning('[Canvas] uploadImage: imageUpload is null, aborting');

                return;
            }

            $user = Auth::user();

            // Check limit before uploading
            $limitChecker = app(LimitCheckerService::class);
            if (! $limitChecker->canCreateEntity($user, 'image')) {
                $remaining = $limitChecker->getRemainingCount($user, 'image');
                $this->limitError = "You have reached your image upload limit. Remaining: {$remaining}.";
                $this->imageUpload = null;
                $this->dispatch('notify-error', message: $this->limitError);
                Log::warning('[Canvas] uploadImage: limit reached');

                return;
            }

            $this->limitError = '';

            Gate::authorize('create', Image::class);

            $image = $imageService->store($user, $this->imageUpload);
            Log::info('[Canvas] Image stored', ['imageId' => $image->id, 'path' => $image->path]);

            $position = $service->assignDefaultPosition($user, $image->id, 'image', $this->viewportCenterX, $this->viewportCenterY);
            Log::info('[Canvas] Position assigned', ['positionId' => $position->id, 'x' => $position->x, 'y' => $position->y]);

            $card = [
                'id' => $image->id,
                'type' => 'image',
                'title' => $image->alt ?? '',
                'preview' => $image->alt ?? '',
                'mood' => null,
                'color_override' => null,
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
            Log::info('[Canvas] uploadImage completed successfully', ['cardId' => $card['id']]);
        } catch (\Throwable $e) {
            Log::error('[Canvas] uploadImage failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function saveEditor(DesktopService $service): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->editorBody = $this->normalizePlainText($this->editorBody);
        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;

        if ($mood !== Mood::Custom) {
            $this->editorColorOverride = null;
        }

        $saved = false;

        if ($this->editingEntityId !== '') {
            $this->updateExistingEntity($user, $mood);
            $saved = true;
        } else {
            $saved = $this->createNewEntity($user, $mood, $service);
        }

        if (! $saved) {
            return;
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
        $this->editorTitle = (string) (data_get($model, 'title') ?? '');
        $this->editorBody = $this->normalizePlainText((string) (data_get($model, 'body') ?? ''));
        $this->editorMood = $this->moodValue(data_get($model, 'mood'), 'plain');
        $this->editorColorOverride = data_get($model, 'color_override') ?? null;
        $this->editorRemindAt = ($entityType === 'reminder' && data_get($model, 'remind_at'))
            ? Carbon::parse((string) data_get($model, 'remind_at'))->format('Y-m-d\TH:i')
            : '';
        $this->loadTagsForEditor($model);
        $this->showEditorModal = true;
    }

    public function openReadonlyModal(string $entityId, string $entityType): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('view', $model);

        $this->showReadonlyModal = true;
        $this->readonlyEntityType = $entityType;
        $this->readonlyOwnerUsername = $model->user->username ?? $model->user->name ?? '';
        $this->readonlyTitle = $model->title ?? '';
        $this->readonlyBody = $model->body ?? $model->alt ?? '';
        $this->readonlyImageUrl = $entityType === 'image' ? route('images.serve', $model) : '';
        $this->readonlyUpdatedAt = data_get($model, 'updated_at') ? Carbon::parse((string) data_get($model, 'updated_at'))->format('d/m/Y H:i') : '';
    }

    public function closeReadonlyModal(): void
    {
        $this->showReadonlyModal = false;
        $this->readonlyEntityType = '';
        $this->readonlyOwnerUsername = '';
        $this->readonlyTitle = '';
        $this->readonlyBody = '';
        $this->readonlyImageUrl = '';
        $this->readonlyUpdatedAt = '';
    }

    public function uploadEditorImage(EditorImageService $service): void
    {
        if (! $this->editorImage) {
            return;
        }

        $user = Auth::user();

        $limitChecker = app(LimitCheckerService::class);
        if (! $limitChecker->canCreateEntity($user, 'image')) {
            $remaining = $limitChecker->getRemainingCount($user, 'image');
            $this->limitError = "You have reached your image upload limit. Remaining: {$remaining}.";
            $this->editorImage = null;
            $this->dispatch('notify-error', message: $this->limitError);

            return;
        }

        $this->limitError = '';

        Gate::authorize('create', Image::class);

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
        $body = $this->normalizePlainText($this->editorBody);
        $data = ['body' => $body, 'mood' => $mood, 'color_override' => $this->editorColorOverride];

        if (in_array($card['type'], ['diary_entry', 'note', 'reminder'], true)) {
            $data['title'] = $this->editorTitle;
        }

        if ($card['type'] === 'reminder' && $this->editorRemindAt !== '') {
            $data['remind_at'] = Carbon::parse($this->editorRemindAt);
        }

        $model->update($data);

        if ($model instanceof DiaryEntry || $model instanceof Note || $model instanceof Reminder) {
            $model->tags()->sync($this->editorTagIds);
        }

        $this->updateCardInList($this->editingEntityId, [
            'title' => $this->editorTitle,
            'preview' => Str::limit($body, 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
        ]);
    }

    public function loadTagsForEditor(?Model $model = null): void
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

        $plainBody = $this->normalizePlainText($body);
        $model->update(['body' => $plainBody]);

        $this->updateCardInList($entityId, [
            'preview' => Str::limit($plainBody, 120),
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

    public function toggleHidden(string $entityId, string $entityType): void
    {
        $user = Auth::user();

        $position = EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->where('context', 'desktop')
            ->first();

        if (! $position) {
            return;
        }

        $position->update(['is_hidden' => ! $position->is_hidden]);

        $this->updateCardInList($entityId, ['is_hidden' => $position->is_hidden]);

        $this->dispatch('card-visibility-changed', entityId: $entityId, isHidden: $position->is_hidden);
    }

    public function loadFriendsForSharing(ShareEntityService $service): void
    {
        $user = Auth::user();
        $this->userFriends = $service->getFriendsForUser($user);
    }

    public function loadCurrentShares(ShareEntityService $service, string $entityId, string $entityType): void
    {
        $user = Auth::user();
        $this->currentEntitySharedFriends = array_map(
            'strval',
            $service->getSharedFriendIds($user, $entityId, $entityType),
        );
    }

    public function toggleShareWithFriend(ShareEntityService $service, string $entityId, string $entityType, string $friendId): void
    {
        $model = $this->resolveEntity($entityId, $entityType);
        if (! $model) {
            return;
        }

        Gate::authorize('update', $model);

        $user = Auth::user();

        if (in_array($friendId, $this->currentEntitySharedFriends, true)) {
            $service->unshareWithFriend($user, $entityId, $entityType, $friendId);
            $this->currentEntitySharedFriends = array_values(array_filter(
                $this->currentEntitySharedFriends,
                fn (string $id): bool => $id !== $friendId,
            ));
        } else {
            $service->shareWithFriend($user, $entityId, $entityType, $friendId);
            $this->currentEntitySharedFriends[] = $friendId;
        }
    }

    /**
     * @return array<int, array{id: string, image_url: string, title: string}>
     */
    public function getVisionBoardImages(): array
    {
        $user = Auth::user();

        return Image::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Image $img): array => [
                'id' => $img->id,
                'image_url' => route('images.serve', $img),
                'title' => $img->title ?? $img->alt ?? '',
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.desktop');
    }

    private function resetEditor(): void
    {
        $this->editingEntityId = '';
        $this->editorTitle = '';
        $this->editorBody = '';
        $this->editorMood = Auth::user()->activeTheme();
        $this->editorColorOverride = null;
        $this->editorImage = null;
        $this->imageUpload = null;
        $this->editorRemindAt = '';
        $this->editorTagIds = [];
        $this->tagSearch = '';
        $this->availableTags = [];
    }

    private function resolveEntity(string $entityId, string $entityType): ?Model
    {
        $morphMap = Relation::morphMap();
        $class = $morphMap[$entityType] ?? null;

        if (! $class) {
            return null;
        }

        return $class::find($entityId);
    }

    private function createNewEntity(User $user, Mood $mood, DesktopService $service): bool
    {
        // Determine entity type and check limits
        $entityType = $this->editorMode === 'diary' ? 'diary_entry' : 'note';
        $limitChecker = app(LimitCheckerService::class);

        if (! $limitChecker->canCreateEntity($user, $entityType)) {
            $remaining = $limitChecker->getRemainingCount($user, $entityType);
            $typeName = $entityType === 'diary_entry' ? 'diary entry' : 'note';
            $this->limitError = "You have reached your {$typeName} limit for today. Remaining: {$remaining}.";
            $this->dispatch('notify-error', message: $this->limitError);

            return false;
        }

        $this->limitError = '';

        $body = $this->normalizePlainText($this->editorBody);

        $data = [
            'user_id' => $user->id,
            'title' => $this->editorTitle,
            'body' => $body,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
        ];

        if ($this->editorMode === 'diary') {
            Gate::authorize('create', DiaryEntry::class);
            $entity = DiaryEntry::create($data);
            $type = 'diary_entry';
        } else {
            Gate::authorize('create', Note::class);
            $entity = Note::create($data);
            $type = 'note';
        }

        if (! empty($this->editorTagIds)) {
            $entity->tags()->sync($this->editorTagIds);
        }

        $position = $service->assignDefaultPosition($user, $entity->id, $type, $this->viewportCenterX, $this->viewportCenterY);

        $card = [
            'id' => $entity->id,
            'type' => $type,
            'title' => $entity->title ?? '',
            'preview' => Str::limit($body, 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
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

        return true;
    }

    private function updateExistingEntity(User $user, Mood $mood): void
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

        $body = $this->normalizePlainText($this->editorBody);

        $data = [
            'body' => $body,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
        ];

        if (in_array($card['type'], ['diary_entry', 'note', 'reminder'], true)) {
            $data['title'] = $this->editorTitle;
        }

        if ($card['type'] === 'image') {
            $data = ['title' => $this->editorTitle];
        }

        if ($card['type'] === 'reminder' && $this->editorRemindAt !== '') {
            $data['remind_at'] = $this->editorRemindAt;
        }

        $model->update($data);

        if (method_exists($model, 'tags')) {
            $model->tags()->sync($this->editorTagIds);
        }

        $updates = [
            'title' => $this->editorTitle,
            'preview' => Str::limit($body, 120),
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
            'tag_ids' => $this->editorTagIds,
        ];

        if ($card['type'] === 'image') {
            $updates = [
                'title' => $this->editorTitle,
            ];
        }

        $this->updateCardInList($this->editingEntityId, $updates);

        $this->dispatch('card-updated', entityId: $this->editingEntityId, updates: $updates);
    }

    /** @param array<string, mixed> $updates */
    private function updateCardInList(string $entityId, array $updates): void
    {
        foreach ($this->cards as $i => $card) {
            if ($card['id'] === $entityId) {
                $this->cards[$i] = array_merge($card, $updates);
                break;
            }
        }
    }

    private function moodValue(mixed $mood, string $fallback = 'summer'): string
    {
        return $mood instanceof Mood ? $mood->value : (is_string($mood) && $mood !== '' ? $mood : $fallback);
    }

    private function normalizePlainText(string $value): string
    {
        $withLineBreaks = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $value) ?? $value;
        $withParagraphBreaks = preg_replace('/<\s*\/p\s*>/i', "\n", $withLineBreaks) ?? $withLineBreaks;
        $stripped = strip_tags($withParagraphBreaks);
        $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace("/\r\n?/", "\n", $decoded) ?? $decoded;
        $compacted = preg_replace("/\n{3,}/", "\n\n", $normalized) ?? $normalized;

        return trim($compacted);
    }
}
