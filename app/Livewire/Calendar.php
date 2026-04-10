<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Mood;
use App\Models\CalendarDayMood;
use App\Models\DiaryEntry;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\Tag;
use App\Services\LimitCheckerService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendar')]
class Calendar extends Component
{
    public int $year;

    public int $month;

    public string $selectedDate = '';

    public string $filterType = 'all';

    public string $filterTag = '';

    /** Modal state for viewing full entity */
    public bool $showEntityModal = false;

    public string $modalEntityType = '';

    public string $modalEntityId = '';

    public string $modalEntityTitle = '';

    public string $modalEntityBody = '';

    public string $modalEntityMood = '';

    public string $modalEntityTime = '';

    /** Quick-create state */
    public bool $showCreateForm = false;

    public string $createType = 'diary';

    public string $createTitle = '';

    public string $createBody = '';

    /** @var list<string> */
    public array $createTags = [];

    public string $createDate = '';

    public string $limitError = '';

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
        $this->closeCreateForm();
    }

    public function openEntityModal(string $type, string $id): void
    {
        $userId = Auth::id();
        $entity = match ($type) {
            'diary' => DiaryEntry::where('user_id', $userId)->find($id),
            'note' => Note::where('user_id', $userId)->find($id),
            'postit' => Postit::where('user_id', $userId)->find($id),
            'reminder' => Reminder::where('user_id', $userId)->find($id),
            default => null,
        };

        if (! $entity) {
            return;
        }

        $this->modalEntityType = $type;
        $this->modalEntityId = (string) $id;
        if ($type === 'postit') {
            $this->modalEntityTitle = 'Post-it';
        } else {
            $this->modalEntityTitle = (string) (data_get($entity, 'title') ?: 'Untitled');
        }
        $this->modalEntityBody = $entity->body ?? '';
        $this->modalEntityMood = $this->moodValue($entity->mood);
        $this->modalEntityTime = $entity->created_at->format('H:i');
        $this->showEntityModal = true;
    }

    public function closeEntityModal(): void
    {
        $this->showEntityModal = false;
        $this->modalEntityType = '';
        $this->modalEntityId = '';
        $this->modalEntityTitle = '';
        $this->modalEntityBody = '';
        $this->modalEntityMood = '';
        $this->modalEntityTime = '';
    }

    public function deleteModalEntity(): void
    {
        if (! $this->canManageModalEntity()) {
            return;
        }

        $entity = $this->resolveModalEntityModel();
        if (! $entity) {
            return;
        }

        Gate::authorize('delete', $entity);
        $entity->delete();

        $this->closeEntityModal();
    }

    public function openModalEntityInCanvas(): void
    {
        if (! $this->canManageModalEntity()) {
            return;
        }

        $entityType = $this->modalEntityCanvasType();
        if ($entityType === null || $this->modalEntityId === '') {
            return;
        }

        $this->redirectRoute('canvas', [
            'edit_entity_id' => $this->modalEntityId,
            'edit_entity_type' => $entityType,
        ], navigate: true);
    }

    public function canManageModalEntity(): bool
    {
        return $this->modalEntityCanvasType() !== null && $this->modalEntityId !== '';
    }

    public function openCreateForm(string $type = 'diary', string $tag = ''): void
    {
        $this->createType = $type;
        $this->createTitle = '';
        $this->createBody = '';
        $this->showCreateForm = true;
        $this->createDate = $this->selectedDate;
        if ($tag !== '') {
            $this->createTags = [$tag];
        }
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
        $this->createTitle = '';
        $this->createBody = '';
        $this->createTags = [];
        $this->createDate = '';
    }

    public function saveNewEntity(): void
    {
        $user = Auth::user();
        $mood = Mood::tryFrom($user->activeTheme()) ?? Mood::Summer;

        $limitEntityType = match ($this->createType) {
            'diary' => 'diary_entry',
            'note' => 'note',
            'postit' => 'postit',
            'reminder' => 'reminder',
            default => null,
        };

        if ($limitEntityType !== null) {
            $limitChecker = app(LimitCheckerService::class);

            if (! $limitChecker->canCreateEntity($user, $limitEntityType)) {
                $remaining = $limitChecker->getRemainingCount($user, $limitEntityType);
                $typeLabel = str_replace('_', ' ', $limitEntityType);
                $this->limitError = "You have reached your {$typeLabel} limit. Remaining: {$remaining}.";
                $this->dispatch('notify-error', message: $this->limitError);

                return;
            }
        }

        $this->limitError = '';

        $createdAt = null;
        if ($this->createDate !== '') {
            $createdAt = Carbon::parse($this->createDate);
        }

        $entity = match ($this->createType) {
            'diary' => (function () use ($user, $mood, $createdAt) {
                Gate::authorize('create', DiaryEntry::class);

                return DiaryEntry::create([
                    'user_id' => $user->id,
                    'title' => $this->createTitle ?: 'Untitled',
                    'body' => $this->createBody,
                    'mood' => $mood,
                    'created_at' => $createdAt,
                ]);
            })(),
            'note' => (function () use ($user, $mood, $createdAt) {
                Gate::authorize('create', Note::class);

                return Note::create([
                    'user_id' => $user->id,
                    'title' => $this->createTitle ?: 'Untitled',
                    'body' => $this->createBody,
                    'mood' => $mood,
                    'created_at' => $createdAt,
                ]);
            })(),
            'postit' => (function () use ($user, $mood, $createdAt) {
                Gate::authorize('create', Postit::class);

                return Postit::create([
                    'user_id' => $user->id,
                    'body' => $this->createBody,
                    'mood' => $mood,
                    'created_at' => $createdAt,
                ]);
            })(),
            'reminder' => (function () use ($user, $mood, $createdAt) {
                Gate::authorize('create', Reminder::class);

                return Reminder::create([
                    'user_id' => $user->id,
                    'title' => $this->createTitle ?: 'Reminder',
                    'body' => $this->createBody,
                    'remind_at' => $this->selectedDate ? Carbon::parse($this->selectedDate)->setTime(9, 0) : now()->addDay(),
                    'mood' => $mood,
                    'created_at' => $createdAt,
                ]);
            })(),
            default => null,
        };

        if (! $entity instanceof Model) {
            return;
        }

        if (! empty($this->createTags)) {
            if (in_array('menstruation', $this->createTags) && $this->selectedDate !== '') {
                CalendarDayMood::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $this->selectedDate],
                    ['mood' => 'love'],
                );
            }
            if (in_array('ovulation', $this->createTags) && $this->selectedDate !== '') {
                CalendarDayMood::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $this->selectedDate],
                    ['mood' => 'breeze'],
                );
            }
        }

        if (! empty($this->createTags)) {
            $tagIds = Tag::whereIn('name', $this->createTags)->pluck('id')->toArray();
            $entity->tags()->sync($tagIds);
        }

        $this->closeCreateForm();
    }

    public function setDayMood(string $date, string $mood): void
    {
        $userId = Auth::id();

        if ($mood === '') {
            CalendarDayMood::where('user_id', $userId)->where('date', $date)->delete();

            return;
        }

        CalendarDayMood::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['mood' => $mood],
        );
    }

    public function render(): View
    {
        $userId = Auth::id();
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        $diaryEntries = $this->shouldShowType('diary')
            ? DiaryEntry::where('user_id', $userId)
                ->with('tags')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $notes = $this->shouldShowType('note')
            ? Note::where('user_id', $userId)
                ->with('tags')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $postits = $this->shouldShowType('postit')
            ? Postit::where('user_id', $userId)
                ->with('tags')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $importantDates = ImportantDate::where('user_id', $userId)->get();

        $reminders = $this->shouldShowType('all') || $this->filterType === 'reminder'
            ? Reminder::where('user_id', $userId)
                ->with('tags')
                ->whereMonth('remind_at', $this->month)
                ->whereYear('remind_at', $this->year)
                ->when($this->filterTag !== '', fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->where('tags.id', $this->filterTag)))
                ->get()
            : collect();

        $calendarDays = $this->buildCalendarGrid($startOfMonth, $diaryEntries, $notes, $postits, $importantDates, $reminders);

        $selectedDayEntities = $this->selectedDate !== ''
            ? $this->getEntitiesForDate($this->selectedDate, $diaryEntries, $notes, $postits, $importantDates, $reminders)
            : collect();

        $userTags = Tag::forUser(Auth::id())->orderBy('name')->get();

        $dayMoods = CalendarDayMood::where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn (CalendarDayMood $dm): string => Carbon::parse((string) $dm->date)->toDateString());

        return view('livewire.calendar-view', [
            'calendarDays' => $calendarDays,
            'selectedDayEntities' => $selectedDayEntities,
            'monthName' => $startOfMonth->translatedFormat('F Y'),
            'userTags' => $userTags,
            'dayMoods' => $dayMoods,
        ]);
    }

    private function shouldShowType(string $type): bool
    {
        return $this->filterType === 'all' || $this->filterType === $type;
    }

    /**
     * @param  Collection<int, DiaryEntry>  $diaryEntries
     * @param  Collection<int, Note>  $notes
     * @param  Collection<int, Postit>  $postits
     * @param  Collection<int, ImportantDate>  $importantDates
     * @param  Collection<int, Reminder>  $reminders
     * @return array<int, array{date: string, day: int, inMonth: bool, isToday: bool, entities: Collection<int, array<string, mixed>>}>
     */
    private function buildCalendarGrid(Carbon $startOfMonth, Collection $diaryEntries, Collection $notes, Collection $postits, Collection $importantDates, Collection $reminders): array
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

            $entities = $this->getEntitiesForDate($date, $diaryEntries, $notes, $postits, $importantDates, $reminders);

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

    /**
     * @param  Collection<int, DiaryEntry>  $diaryEntries
     * @param  Collection<int, Note>  $notes
     * @param  Collection<int, Postit>  $postits
     * @param  Collection<int, ImportantDate>  $importantDates
     * @param  Collection<int, Reminder>  $reminders
     * @return Collection<int, array<string, mixed>>
     */
    private function getEntitiesForDate(string $date, Collection $diaryEntries, Collection $notes, Collection $postits, Collection $importantDates, Collection $reminders): Collection
    {
        $entities = collect();
        $parsedDate = Carbon::parse($date);

        $diaryEntries->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'diary',
                'id' => $e->id,
                'title' => $e->title ?: 'Untitled',
                'mood' => $this->moodValue($e->mood),
                'preview' => str(strip_tags($e->body ?? ''))->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        $notes->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'note',
                'id' => $e->id,
                'title' => $e->title ?: 'Untitled',
                'mood' => $this->moodValue($e->mood),
                'preview' => str(strip_tags($e->body ?? ''))->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        $postits->filter(fn ($e) => $e->created_at->toDateString() === $date)
            ->each(fn ($e) => $entities->push([
                'type' => 'postit',
                'id' => $e->id,
                'title' => 'Post-it',
                'mood' => $this->moodValue($e->mood),
                'preview' => str(strip_tags($e->body ?? ''))->limit(80)->toString(),
                'created_at' => $e->created_at,
            ]));

        // Important dates (exact match or recurring annual match)
        $importantDates->filter(function (ImportantDate $d) use ($parsedDate): bool {
            $importantDate = Carbon::parse((string) $d->date);

            if ($importantDate->toDateString() === $parsedDate->toDateString()) {
                return true;
            }

            return $d->recurs_annually && $importantDate->month === $parsedDate->month && $importantDate->day === $parsedDate->day;
        })->each(fn ($d) => $entities->push([
            'type' => 'important_date',
            'id' => $d->id,
            'title' => $d->label,
            'mood' => 'love',
            'preview' => $d->recurs_annually ? __('Yearly') : '',
            'created_at' => Carbon::parse((string) $d->date)->setYear($parsedDate->year),
        ]));

        // Reminders
        $reminders->filter(fn (Reminder $r): bool => Carbon::parse((string) $r->remind_at)->toDateString() === $date)
            ->each(fn ($r) => $entities->push([
                'type' => 'reminder',
                'id' => $r->id,
                'title' => $r->title,
                'mood' => $this->moodValue($r->mood),
                'preview' => str(strip_tags($r->body ?? ''))->limit(80)->toString(),
                'created_at' => Carbon::parse((string) $r->remind_at),
            ]));

        return $entities->sortBy('created_at')->values();
    }

    private function moodValue(mixed $mood): string
    {
        return $mood instanceof Mood ? $mood->value : (is_string($mood) && $mood !== '' ? $mood : 'summer');
    }

    private function modalEntityCanvasType(): ?string
    {
        return match ($this->modalEntityType) {
            'diary' => 'diary_entry',
            'note' => 'note',
            'postit' => 'postit',
            'reminder' => 'reminder',
            default => null,
        };
    }

    private function resolveModalEntityModel(): ?Model
    {
        $userId = Auth::id();

        return match ($this->modalEntityType) {
            'diary' => DiaryEntry::where('user_id', $userId)->find($this->modalEntityId),
            'note' => Note::where('user_id', $userId)->find($this->modalEntityId),
            'postit' => Postit::where('user_id', $userId)->find($this->modalEntityId),
            'reminder' => Reminder::where('user_id', $userId)->find($this->modalEntityId),
            default => null,
        };
    }
}
