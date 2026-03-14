<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\Tag;
use App\Services\ReminderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Diary')]
class DiaryView extends Component
{
    public string $displayMode = 'paginated';

    public int $currentPage = 1;

    public int $entriesPerSpread = 2;

    public string $editingEntryId = '';

    public string $editTitle = '';

    public string $editBody = '';

    public bool $showNewEntryForm = false;

    public string $newTitle = '';

    public string $newBody = '';

    public string $search = '';

    /** Uplifting entry suggestion after a "sad" entry */
    public ?string $upliftTitle = null;

    public ?string $upliftPreview = null;

    /** @var array<int, string> */
    public array $editTagIds = [];

    public string $tagSearch = '';

    /** @var array<int, array{id: string, name: string}> */
    public array $availableTags = [];

    /** @var array<int, string> */
    public array $newTagIds = [];

    public string $newTagSearch = '';

    public function mount(): void
    {
        $this->displayMode = Auth::user()->diary_display_mode ?? 'paginated';
    }

    public function toggleDisplayMode(): void
    {
        $this->displayMode = $this->displayMode === 'scroll' ? 'paginated' : 'scroll';
        $this->currentPage = 1;

        Auth::user()->update(['diary_display_mode' => $this->displayMode]);
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function nextPage(): void
    {
        $total = $this->getTotalPages();
        if ($this->currentPage < $total) {
            $this->currentPage++;
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function startEditing(string $entryId): void
    {
        $entry = DiaryEntry::find($entryId);
        if (! $entry) {
            return;
        }

        Gate::authorize('update', $entry);

        $this->editingEntryId = $entryId;
        $this->editTitle = $entry->title ?? '';
        $this->editBody = $entry->body ?? '';
        $this->editTagIds = $entry->tags()->pluck('tags.id')->all();
        $this->loadAvailableTags();
    }

    public function cancelEditing(): void
    {
        $this->editingEntryId = '';
        $this->editTitle = '';
        $this->editBody = '';
        $this->editTagIds = [];
        $this->tagSearch = '';
        $this->availableTags = [];
    }

    public function saveEntry(): void
    {
        if ($this->editingEntryId === '') {
            return;
        }

        $entry = DiaryEntry::find($this->editingEntryId);
        if (! $entry) {
            return;
        }

        Gate::authorize('update', $entry);

        $entry->update([
            'title' => $this->editTitle,
            'body' => $this->editBody,
        ]);

        $entry->tags()->sync($this->editTagIds);

        $this->checkForSadEntry($entry);
        $this->cancelEditing();
    }

    public function openNewEntry(): void
    {
        $this->showNewEntryForm = true;
        $this->newTitle = '';
        $this->newBody = '';
        $this->newTagIds = [];
        $this->newTagSearch = '';
        $this->loadAvailableTags();
    }

    public function cancelNewEntry(): void
    {
        $this->showNewEntryForm = false;
        $this->newTitle = '';
        $this->newBody = '';
        $this->newTagIds = [];
        $this->newTagSearch = '';
    }

    public function toggleEditTag(string $tagId): void
    {
        if (in_array($tagId, $this->editTagIds, true)) {
            $this->editTagIds = array_values(array_filter(
                $this->editTagIds,
                fn (string $id): bool => $id !== $tagId,
            ));
        } else {
            $this->editTagIds[] = $tagId;
        }
    }

    public function toggleNewTag(string $tagId): void
    {
        if (in_array($tagId, $this->newTagIds, true)) {
            $this->newTagIds = array_values(array_filter(
                $this->newTagIds,
                fn (string $id): bool => $id !== $tagId,
            ));
        } else {
            $this->newTagIds[] = $tagId;
        }
    }

    public function createEditTagInline(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $tag = Tag::create([
            'name' => $name,
            'user_id' => Auth::id(),
        ]);

        $this->availableTags[] = ['id' => $tag->id, 'name' => $tag->name];
        $this->editTagIds[] = $tag->id;
        $this->tagSearch = '';
    }

    public function createNewTagInline(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $tag = Tag::create([
            'name' => $name,
            'user_id' => Auth::id(),
        ]);

        $this->availableTags[] = ['id' => $tag->id, 'name' => $tag->name];
        $this->newTagIds[] = $tag->id;
        $this->newTagSearch = '';
    }

    public function changeMood(string $entryId, string $mood): void
    {
        $entry = DiaryEntry::find($entryId);
        if (! $entry) {
            return;
        }

        Gate::authorize('update', $entry);

        $moodEnum = Mood::tryFrom($mood);
        if ($moodEnum) {
            $entry->update(['mood' => $moodEnum]);
        }
    }

    public function createEntry(): void
    {
        $user = Auth::user();

        $entry = DiaryEntry::create([
            'user_id' => $user->id,
            'title' => $this->newTitle,
            'body' => $this->newBody,
            'mood' => Mood::tryFrom($user->theme ?? 'summer') ?? Mood::Summer,
            'is_public' => false,
        ]);

        if (! empty($this->newTagIds)) {
            $entry->tags()->sync($this->newTagIds);
        }

        $this->checkForSadEntry($entry);
        $this->cancelNewEntry();
    }

    private function checkForSadEntry(DiaryEntry $entry): void
    {
        $sadTagNames = ['sad', 'anxious'];
        $hasSadTag = $entry->tags()->whereIn('name', $sadTagNames)->exists();

        if (! $hasSadTag) {
            $this->upliftTitle = null;
            $this->upliftPreview = null;

            return;
        }

        $service = new ReminderService();
        $uplift = $service->findUpliftingEntry(Auth::user());

        if ($uplift && $uplift->id !== $entry->id) {
            $this->upliftTitle = $uplift->title ?: 'Untitled';
            $this->upliftPreview = str(strip_tags($uplift->body ?? ''))->limit(150)->toString();
        }
    }

    public function dismissUplift(): void
    {
        $this->upliftTitle = null;
        $this->upliftPreview = null;
    }

    public function render(): View
    {
        $user = Auth::user();

        $query = DiaryEntry::query()
            ->where('user_id', $user->id)
            ->with('tags')
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('body', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->displayMode === 'paginated') {
            $entries = $query->get();
            $total = $entries->count();
            $totalPages = (int) max(1, ceil($total / $this->entriesPerSpread));
            $this->currentPage = min($this->currentPage, $totalPages);

            $offset = ($this->currentPage - 1) * $this->entriesPerSpread;
            $pagedEntries = $entries->slice($offset, $this->entriesPerSpread)->values();

            return view('livewire.diary-view', [
                'entries' => $pagedEntries,
                'totalPages' => $totalPages,
                'allEntries' => collect(),
            ]);
        }

        $allEntries = $query->get();

        return view('livewire.diary-view', [
            'entries' => collect(),
            'totalPages' => 1,
            'allEntries' => $allEntries,
        ]);
    }

    private function getTotalPages(): int
    {
        $query = DiaryEntry::where('user_id', Auth::id());

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('body', 'like', '%' . $this->search . '%');
            });
        }

        $total = $query->count();

        return (int) max(1, ceil($total / $this->entriesPerSpread));
    }

    private function loadAvailableTags(): void
    {
        $this->availableTags = Tag::forUser(Auth::id())
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => ['id' => $tag->id, 'name' => $tag->name])
            ->all();
    }
}
