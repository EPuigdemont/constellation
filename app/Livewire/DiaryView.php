<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
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
    }

    public function cancelEditing(): void
    {
        $this->editingEntryId = '';
        $this->editTitle = '';
        $this->editBody = '';
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

        $this->cancelEditing();
    }

    public function openNewEntry(): void
    {
        $this->showNewEntryForm = true;
        $this->newTitle = '';
        $this->newBody = '';
    }

    public function cancelNewEntry(): void
    {
        $this->showNewEntryForm = false;
        $this->newTitle = '';
        $this->newBody = '';
    }

    public function createEntry(): void
    {
        $user = Auth::user();

        DiaryEntry::create([
            'user_id' => $user->id,
            'title' => $this->newTitle,
            'body' => $this->newBody,
            'mood' => Mood::tryFrom($user->theme ?? 'summer') ?? Mood::Summer,
            'is_public' => false,
        ]);

        $this->cancelNewEntry();
    }

    public function render(): View
    {
        $user = Auth::user();

        $query = DiaryEntry::query()
            ->where('user_id', $user->id)
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
}
