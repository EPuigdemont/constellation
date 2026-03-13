<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\Postit;
use App\Services\DesktopService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Desktop')]
class Desktop extends Component
{
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

    public function mount(DesktopService $service): void
    {
        $user = Auth::user();
        $this->zoom = $user->desktop_zoom ?? 1.0;
        $this->cards = $service->loadCards($user);
        $this->maxZIndex = $service->nextZIndex($user) - 1;
    }

    public function savePosition(DesktopService $service, string $entityId, string $entityType, float $x, float $y, int $zIndex): void
    {
        $service->savePosition(Auth::user(), $entityId, $entityType, $x, $y, $zIndex);
    }

    public function bringToFront(DesktopService $service, string $entityId, string $entityType): int
    {
        $user = Auth::user();
        $newZ = $service->nextZIndex($user);
        $service->savePosition($user, $entityId, $entityType, 0, 0, $newZ);

        // Update the position without resetting x/y — the frontend has current coords
        $position = \App\Models\EntityPosition::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $entityId)
            ->where('entity_type', $entityType)
            ->first();

        if ($position) {
            $position->update(['z_index' => $newZ]);
        }

        $this->maxZIndex = $newZ;

        return $newZ;
    }

    public function saveZoom(DesktopService $service, float $zoom): void
    {
        $this->zoom = $zoom;
        $service->saveZoom(Auth::user(), $zoom);
    }

    public function openDiaryModal(): void
    {
        $this->resetEditor();
        $this->editorMode = 'diary';
        $this->showEditorModal = true;
    }

    public function openNoteModal(): void
    {
        $this->resetEditor();
        $this->editorMode = 'note';
        $this->showEditorModal = true;
    }

    public function createPostit(DesktopService $service): void
    {
        $user = Auth::user();

        $postit = Postit::create([
            'user_id' => $user->id,
            'body' => '',
            'mood' => Mood::Plain,
            'is_public' => false,
        ]);

        $position = $service->assignDefaultPosition($user, $postit->id, 'postit');

        $this->cards[] = [
            'id' => $postit->id,
            'type' => 'postit',
            'title' => '',
            'preview' => '',
            'mood' => 'plain',
            'color_override' => null,
            'is_public' => false,
            'x' => $position->x,
            'y' => $position->y,
            'z_index' => $position->z_index,
            'owner_id' => $user->id,
        ];

        $this->maxZIndex = $position->z_index;
    }

    public function saveEditor(DesktopService $service): void
    {
        $user = Auth::user();
        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;

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
        $this->showEditorModal = true;
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
        if ($this->editorMode === 'diary') {
            $entity = DiaryEntry::create([
                'user_id' => $user->id,
                'title' => $this->editorTitle,
                'body' => $this->editorBody,
                'mood' => $mood,
                'is_public' => false,
            ]);
            $type = 'diary_entry';
        } else {
            $entity = Note::create([
                'user_id' => $user->id,
                'title' => $this->editorTitle,
                'body' => $this->editorBody,
                'mood' => $mood,
                'is_public' => false,
            ]);
            $type = 'note';
        }

        $position = $service->assignDefaultPosition($user, $entity->id, $type);

        $this->cards[] = [
            'id' => $entity->id,
            'type' => $type,
            'title' => $entity->title ?? '',
            'preview' => \Illuminate\Support\Str::limit($entity->body ?? '', 120),
            'mood' => $mood->value,
            'color_override' => null,
            'is_public' => false,
            'x' => $position->x,
            'y' => $position->y,
            'z_index' => $position->z_index,
            'owner_id' => $user->id,
        ];

        $this->maxZIndex = $position->z_index;
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

        $data = ['body' => $this->editorBody, 'mood' => $mood];

        if (in_array($card['type'], ['diary_entry', 'note'], true)) {
            $data['title'] = $this->editorTitle;
        }

        $model->update($data);

        $this->updateCardInList($this->editingEntityId, [
            'title' => $this->editorTitle,
            'preview' => \Illuminate\Support\Str::limit($this->editorBody, 120),
            'mood' => $mood->value,
        ]);
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
