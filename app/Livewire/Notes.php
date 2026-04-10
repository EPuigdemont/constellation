<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\LimitCheckerService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notes')]
class Notes extends Component
{
    public string $search = '';

    /** @var array<int, string> */
    public array $expandedDays = [];

    public bool $showEditorModal = false;

    public string $editingNoteId = '';

    public string $editorTitle = '';

    public string $editorBody = '';

    public string $editorMood = 'plain';

    public ?string $editorColorOverride = null;

    public string $editorDate = '';

    /** @var array<int, string> */
    public array $editorTagIds = [];

    public string $tagSearch = '';

    /** @var array<int, array{id: string, name: string, color: string|null}> */
    public array $availableTags = [];

    public string $limitError = '';

    public function mount(): void
    {
        $this->editorDate = now()->toDateString();
        $this->editorMood = Auth::user()->activeTheme();
    }

    public function updatedSearch(): void
    {
        // Keep expansion state stable while filtering; only explicit user toggles change it.
    }

    public function toggleDayExpansion(string $date): void
    {
        if (in_array($date, $this->expandedDays, true)) {
            $this->expandedDays = array_values(array_filter(
                $this->expandedDays,
                fn (string $expandedDate): bool => $expandedDate !== $date,
            ));

            return;
        }

        $this->expandedDays[] = $date;
    }

    public function openNoteModal(?string $date = null): void
    {
        $this->resetEditor();
        $this->editorDate = $date ?: now()->toDateString();
        $this->showEditorModal = true;
        $this->loadTagsForEditor();
    }

    public function openEditModal(string $noteId): void
    {
        $note = Note::with('tags')->find($noteId);
        if (! $note) {
            return;
        }

        Gate::authorize('update', $note);

        $this->resetEditor();
        $this->editingNoteId = $note->id;
        $this->editorTitle = (string) ($note->title ?? '');
        $this->editorBody = $this->normalizePlainText($note->body ?? '');
        $this->editorMood = $this->moodValue($note->mood, 'plain');
        $this->editorColorOverride = $note->color_override;
        $this->editorDate = $note->created_at?->toDateString() ?? now()->toDateString();
        $this->loadTagsForEditor($note);
        $this->showEditorModal = true;
    }

    public function saveEditor(): void
    {
        $this->validate([
            'editorTitle' => 'nullable|string|max:255',
            'editorBody' => 'nullable|string',
            'editorDate' => 'required|date',
            'editorMood' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $mood = Mood::tryFrom($this->editorMood) ?? Mood::Plain;

        if ($mood !== Mood::Custom) {
            $this->editorColorOverride = null;
        }

        $saved = $this->editingNoteId === ''
            ? $this->createNewNote($user, $mood)
            : $this->updateExistingNote($mood);

        if (! $saved) {
            return;
        }

        $this->showEditorModal = false;
        if (! in_array($this->editorDate, $this->expandedDays, true)) {
            $this->expandedDays[] = $this->editorDate;
        }
        $this->resetEditor();
    }

    public function deleteFromEditor(): void
    {
        if ($this->editingNoteId === '') {
            return;
        }

        $note = Note::find($this->editingNoteId);
        if (! $note) {
            return;
        }

        Gate::authorize('delete', $note);

        $deletedDate = $note->created_at?->toDateString() ?? '';
        $note->delete();

        if (in_array($deletedDate, $this->expandedDays, true)) {
            $remainingInDay = Note::query()
                ->where('user_id', Auth::id())
                ->whereDate('created_at', $deletedDate)
                ->exists();

            if (! $remainingInDay) {
                $this->expandedDays = array_values(array_filter(
                    $this->expandedDays,
                    fn (string $expandedDate): bool => $expandedDate !== $deletedDate,
                ));
            }
        }

        $this->showEditorModal = false;
        $this->resetEditor();
    }

    public function toggleTag(string $tagId): void
    {
        if (in_array($tagId, $this->editorTagIds, true)) {
            $this->editorTagIds = array_values(array_filter(
                $this->editorTagIds,
                fn (string $id): bool => $id !== $tagId,
            ));

            return;
        }

        $this->editorTagIds[] = $tagId;
    }

    public function createTagInline(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $tag = Tag::create([
            'name' => $name,
            'user_id' => Auth::id(),
        ]);

        $this->availableTags[] = [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ];

        $this->editorTagIds[] = $tag->id;
        $this->tagSearch = '';
    }

    public function render(): View
    {
        $notes = Note::query()
            ->where('user_id', Auth::id())
            ->with('tags')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('body', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->get();

        /** @var Collection<string, Collection<int, Note>> $notesByDay */
        $notesByDay = $notes->groupBy(fn (Note $note): string => $note->created_at?->toDateString() ?? now()->toDateString());

        return view('livewire.notes-view', [
            'notesByDay' => $notesByDay,
        ]);
    }

    public function closeEditor(): void
    {
        $this->showEditorModal = false;
        $this->resetEditor();
    }

    private function resetEditor(): void
    {
        $this->editingNoteId = '';
        $this->editorTitle = '';
        $this->editorBody = '';
        $this->editorMood = Auth::user()->activeTheme();
        $this->editorColorOverride = null;
        $this->editorDate = now()->toDateString();
        $this->editorTagIds = [];
        $this->tagSearch = '';
        $this->availableTags = [];
    }

    private function createNewNote(User $user, Mood $mood): bool
    {
        $limitChecker = app(LimitCheckerService::class);
        if (! $limitChecker->canCreateEntity($user, 'note')) {
            $remaining = $limitChecker->getRemainingCount($user, 'note');
            $this->limitError = __('You have reached your note limit for today. Remaining: :remaining.', ['remaining' => $remaining]);
            $this->dispatch('notify-error', message: $this->limitError);

            return false;
        }

        $this->limitError = '';

        Gate::authorize('create', Note::class);

        $body = $this->normalizePlainText($this->editorBody);
        $createdAt = Carbon::parse($this->editorDate)->setTimeFrom(now());

        $note = Note::create([
            'user_id' => $user->id,
            'title' => trim($this->editorTitle) !== '' ? trim($this->editorTitle) : 'Untitled',
            'body' => $body,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
            'created_at' => $createdAt,
        ]);

        if (! empty($this->editorTagIds)) {
            $note->tags()->sync($this->editorTagIds);
        }

        return true;
    }

    private function updateExistingNote(Mood $mood): bool
    {
        $note = Note::find($this->editingNoteId);
        if (! $note) {
            return false;
        }

        Gate::authorize('update', $note);

        $body = $this->normalizePlainText($this->editorBody);
        $createdAt = Carbon::parse($this->editorDate)->setTimeFrom($note->created_at ?? now());

        $note->update([
            'title' => trim($this->editorTitle) !== '' ? trim($this->editorTitle) : 'Untitled',
            'body' => $body,
            'mood' => $mood,
            'color_override' => $this->editorColorOverride,
            'created_at' => $createdAt,
        ]);

        $note->tags()->sync($this->editorTagIds);

        return true;
    }

    private function loadTagsForEditor(?Note $note = null): void
    {
        $this->availableTags = Tag::forUser(Auth::id())
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])
            ->all();

        if ($note) {
            $this->editorTagIds = $note->tags->pluck('id')->all();
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

        return Str::of($compacted)->trim()->toString();
    }
}
