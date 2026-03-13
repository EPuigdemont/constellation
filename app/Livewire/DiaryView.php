<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\DiaryEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Diary')]
class DiaryView extends Component
{
    public string $displayMode = 'scroll';

    public int $currentPage = 1;

    public int $entriesPerSpread = 2;

    public function toggleDisplayMode(): void
    {
        $this->displayMode = $this->displayMode === 'scroll' ? 'paginated' : 'scroll';
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

    public function render(): View
    {
        $user = Auth::user();

        $query = DiaryEntry::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

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
        $total = DiaryEntry::where('user_id', Auth::id())->count();

        return (int) max(1, ceil($total / $this->entriesPerSpread));
    }
}
