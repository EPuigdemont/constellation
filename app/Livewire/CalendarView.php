<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\Postit;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendar')]
class CalendarView extends Component
{
    public int $year;

    public int $month;

    public string $selectedDate = '';

    public string $filterType = 'all';

    public string $filterTag = '';

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->selectedDate = '';
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->selectedDate = '';
    }

    public function goToToday(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->selectedDate = now()->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $this->selectedDate === $date ? '' : $date;
    }

    public function render(): View
    {
        $userId = Auth::id();
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        $diaryEntries = $this->shouldShowType('diary')
            ? DiaryEntry::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $notes = $this->shouldShowType('note')
            ? Note::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $postits = $this->shouldShowType('postit')
            ? Postit::where('user_id', $userId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $calendarDays = $this->buildCalendarGrid($startOfMonth, $diaryEntries, $notes, $postits);

        $selectedDayEntities = $this->selectedDate !== ''
            ? $this->getEntitiesForDate($this->selectedDate, $diaryEntries, $notes, $postits)
            : collect();

        $userTags = Auth::user()->tags()->orderBy('name')->get();

        return view('livewire.calendar-view', [
            'calendarDays' => $calendarDays,
            'selectedDayEntities' => $selectedDayEntities,
            'monthName' => $startOfMonth->translatedFormat('F Y'),
            'userTags' => $userTags,
        ]);
    }

    private function shouldShowType(string $type): bool
    {
        return $this->filterType === 'all' || $this->filterType === $type;
    }

    /**
     * @return array<int, array{date: string, day: int, inMonth: bool, isToday: bool, entities: Collection}>
     */
    private function buildCalendarGrid(Carbon $startOfMonth, Collection $diaryEntries, Collection $notes, Collection $postits): array
    {
        $daysInMonth = $startOfMonth->daysInMonth;
        $startDayOfWeek = $startOfMonth->dayOfWeekIso; // 1=Monday, 7=Sunday

        $grid = [];

        // Padding days from previous month
        $prevMonth = $startOfMonth->copy()->subMonth();
        $prevDays = $prevMonth->daysInMonth;
        for ($i = $startDayOfWeek - 1; $i > 0; $i--) {
            $day = $prevDays - $i + 1;
            $date = $prevMonth->copy()->day($day)->toDateString();
            $grid[] = [
                'date' => $date,
                'day' => $day,
                'inMonth' => false,
                'isToday' => false,
                'entities' => collect(),
            ];
        }

        // Current month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startOfMonth->copy()->day($day)->toDateString();
            $isToday = $date === now()->toDateString();

            $entities = $this->getEntitiesForDate($date, $diaryEntries, $notes, $postits);

            $grid[] = [
                'date' => $date,
                'day' => $day,
                'inMonth' => true,
                'isToday' => $isToday,
                'entities' => $entities,
            ];
        }

        // Padding days for next month to fill remaining cells
        $remaining = 7 - (count($grid) % 7);
        if ($remaining < 7) {
            for ($day = 1; $day <= $remaining; $day++) {
                $nextMonth = $startOfMonth->copy()->addMonth();
                $date = $nextMonth->copy()->day($day)->toDateString();
                $grid[] = [
                    'date' => $date,
                    'day' => $day,
                    'inMonth' => false,
                    'isToday' => false,
                    'entities' => collect(),
                ];
            }
        }

        return $grid;
    }

    private function getEntitiesForDate(string $date, Collection $diaryEntries, Collection $notes, Collection $postits): Collection
    {
        $entities = collect();

        $diaryEntries->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'diary',
                'id' => $e->id,
                'title' => $e->title ?: 'Untitled',
                'mood' => $e->mood?->value ?? 'summer',
                'preview' => str($e->body ?? '')->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        $notes->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'note',
                'id' => $e->id,
                'title' => $e->title ?: 'Untitled',
                'mood' => $e->mood?->value ?? 'summer',
                'preview' => str($e->body ?? '')->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        $postits->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'postit',
                'id' => $e->id,
                'title' => 'Post-it',
                'mood' => $e->mood?->value ?? 'summer',
                'preview' => str($e->body ?? '')->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        return $entities->sortBy('created_at')->values();
    }
}
