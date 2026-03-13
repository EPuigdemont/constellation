<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\Image;
use App\Models\Note;
use App\Models\Tag;
use App\Services\DesktopService;
use App\Services\EditorImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Vision Board')]
class VisionBoard extends Component
{
    use WithFileUploads;

    private const string CONTEXT = 'vision_board';

    public float $zoom = 1.0;

    /** @var array<int, array<string, mixed>> */
    public array $cards = [];

    public int $maxZIndex = 0;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $imageUpload = null;

    /** Editor modal state */
    public bool $showEditorModal = false;

    public string $editingImageId = '';

    public string $editorAlt = '';

    public string $editorMood = 'plain';

    public ?string $editorColorOverride = null;

    /** @var array<int, string> */
    public array $editorTagIds = [];

    public string $tagSearch = '';

    /** @var array<int, array{id: string, name: string, color: string|null}> */
    public array $availableTags = [];

    /** @var array<int, array{id: string, name: string}> */
    public array $filterAvailableTags = [];

    public float $viewportCenterX = 2000.0;

    public float $viewportCenterY = 2000.0;

    /** Linking state */
    public string $linkingMode = '';

    public string $linkingEntityId = '';

    public string $linkingEntityType = '';

    /** Link search modal */
    public bool $showLinkSearchModal = false;

    public string $linkSearchQuery = '';

    public string $linkingSourceId = '';

    /** @var array<int, array{id: string, type: string, title: string}> */
    public array $linkSearchResults = [];

    public function mount(DesktopService $service): void
    {
        $user = Auth::user();
        $this->zoom = $user->vision_board_zoom ?? 1.0;
        $this->cards = $service->loadCards($user, self::CONTEXT, ['image' => Image::class]);
        $this->maxZIndex = $service->nextZIndex($user, self::CONTEXT) - 1;

        $this->filterAvailableTags = Tag::forUser($user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name])
            ->all();
    }

    public function savePosition(DesktopService $service, string $entityId, string $entityType, float $x, float $y, int $zIndex): void
    {
        $service->savePosition(Auth::user(), $entityId, $entityType, $x, $y, $zIndex, self::CONTEXT);
    }

    public function bringToFront(DesktopService $service, string $entityId, string $entityType): int
    {
        $user = Auth::user();
        $newZ = $service->nextZIndex($user, self::CONTEXT);

        EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->where('context', self::CONTEXT)
            ->update(['z_index' => $newZ]);

        $this->maxZIndex = $newZ;

        return $newZ;
    }

    public function saveSize(DesktopService $service, string $entityId, string $entityType, float $width, float $height): void
    {
        $service->saveSize(Auth::user(), $entityId, $entityType, $width, $height, self::CONTEXT);
    }

    public function saveZoom(float $zoom): void
    {
        $this->zoom = $zoom;
        Auth::user()->update(['vision_board_zoom' => $zoom]);
    }

    public function uploadImage(EditorImageService $imageService, DesktopService $service): void
    {
        if (! $this->imageUpload) {
            return;
        }

        $user = Auth::user();
        $image = $imageService->store($user, $this->imageUpload);

        $position = $service->assignDefaultPosition(
            $user,
            $image->id,
            'image',
            $this->viewportCenterX,
            $this->viewportCenterY,
            self::CONTEXT,
        );

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
    }

    public function openEditModal(string $imageId): void
    {
        $image = Image::find($imageId);
        if (! $image) {
            return;
        }

        Gate::authorize('update', $image);

        $this->editingImageId = $image->id;
        $this->editorAlt = $image->alt ?? '';
        $this->editorMood = $image->mood?->value ?? 'plain';
        $this->editorColorOverride = $image->color_override;
        $this->editorTagIds = $image->tags()->pluck('tags.id')->all();

        $user = Auth::user();
        $this->availableTags = Tag::forUser($user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])
            ->all();

        $this->showEditorModal = true;
    }

    public function saveEditor(): void
    {
        $image = Image::find($this->editingImageId);
        if (! $image) {
            return;
        }

        Gate::authorize('update', $image);

        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;

        if ($mood !== Mood::Custom) {
            $this->editorColorOverride = null;
        }

        $image->update([
            'alt' => $this->editorAlt,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
        ]);

        $image->tags()->sync($this->editorTagIds);

        $this->updateCardInList($image->id, [
            'title' => $this->editorAlt,
            'preview' => $this->editorAlt,
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
            'tag_ids' => $this->editorTagIds,
        ]);

        $this->dispatch('card-updated', entityId: $image->id, updates: [
            'title' => $this->editorAlt,
            'preview' => $this->editorAlt,
            'mood' => $mood->value,
            'color_override' => $this->editorColorOverride,
        ]);

        $this->showEditorModal = false;
        $this->resetEditor();
    }

    public function deleteImage(string $imageId): void
    {
        $image = Image::find($imageId);
        if (! $image) {
            return;
        }

        Gate::authorize('delete', $image);

        $image->delete();

        $this->cards = array_values(
            array_filter($this->cards, fn (array $card): bool => $card['id'] !== $imageId),
        );

        $this->dispatch('card-deleted', entityId: $imageId);
    }

    public function changeMood(string $imageId, string $mood): void
    {
        $image = Image::find($imageId);
        if (! $image) {
            return;
        }

        Gate::authorize('update', $image);

        $moodEnum = Mood::tryFrom($mood);
        if (! $moodEnum) {
            return;
        }

        $image->update(['mood' => $moodEnum]);

        $this->updateCardInList($imageId, ['mood' => $mood]);
    }

    public function togglePublic(string $imageId): void
    {
        $image = Image::find($imageId);
        if (! $image) {
            return;
        }

        Gate::authorize('update', $image);

        $image->update(['is_public' => ! $image->is_public]);

        $this->updateCardInList($imageId, ['is_public' => ! $image->is_public]);
    }

    /** Open the link search modal to link an image to a diary entry or note. */
    public function openLinkSearch(string $imageId): void
    {
        $this->linkingSourceId = $imageId;
        $this->linkSearchQuery = '';
        $this->linkSearchResults = [];
        $this->showLinkSearchModal = true;
    }

    public function updatedLinkSearchQuery(): void
    {
        if (mb_strlen($this->linkSearchQuery) < 2) {
            $this->linkSearchResults = [];

            return;
        }

        $user = Auth::user();
        $query = $this->linkSearchQuery;
        $results = [];

        $diaryEntries = DiaryEntry::where('user_id', $user->id)
            ->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($diaryEntries as $entry) {
            $results[] = [
                'id' => $entry->id,
                'type' => 'diary_entry',
                'title' => $entry->title ?: \Illuminate\Support\Str::limit(strip_tags($entry->body ?? ''), 60),
            ];
        }

        $notes = Note::where('user_id', $user->id)
            ->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($notes as $note) {
            $results[] = [
                'id' => $note->id,
                'type' => 'note',
                'title' => $note->title ?: \Illuminate\Support\Str::limit(strip_tags($note->body ?? ''), 60),
            ];
        }

        $this->linkSearchResults = $results;
    }

    public function linkToEntity(DesktopService $service, string $targetId, string $targetType): void
    {
        if (! $this->linkingSourceId) {
            return;
        }

        $service->linkSiblings($this->linkingSourceId, 'image', $targetId, $targetType);

        // Update sibling counts on the card
        foreach ($this->cards as $i => $card) {
            if ($card['id'] === $this->linkingSourceId) {
                $this->cards[$i]['siblings_count'] = ($card['siblings_count'] ?? 0) + 1;
                break;
            }
        }

        $this->showLinkSearchModal = false;
        $this->linkingSourceId = '';
        $this->linkSearchQuery = '';
        $this->linkSearchResults = [];
    }

    public function cancelLinkSearch(): void
    {
        $this->showLinkSearchModal = false;
        $this->linkingSourceId = '';
        $this->linkSearchQuery = '';
        $this->linkSearchResults = [];
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

    public function addTag(): void
    {
        $name = trim($this->tagSearch);
        if ($name === '') {
            return;
        }

        $user = Auth::user();
        $tag = Tag::firstOrCreate(
            ['name' => $name, 'user_id' => $user->id],
        );

        if (! in_array($tag->id, $this->editorTagIds, true)) {
            $this->editorTagIds[] = $tag->id;
        }

        $this->availableTags = Tag::forUser($user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])
            ->all();

        $this->tagSearch = '';
    }

    public function render(): View
    {
        return view('livewire.vision-board');
    }

    private function resetEditor(): void
    {
        $this->editingImageId = '';
        $this->editorAlt = '';
        $this->editorMood = 'plain';
        $this->editorColorOverride = null;
        $this->editorTagIds = [];
        $this->tagSearch = '';
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
