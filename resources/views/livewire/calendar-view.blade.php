<div class="flex h-full flex-col gap-4 p-4 lg:p-6">
    {{-- Header: Month Navigation + Filters --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <flux:button size="sm" variant="subtle" wire:click="previousMonth" icon="chevron-left" />
            <h1 class="min-w-[10rem] text-center text-xl font-semibold text-[var(--theme-text)]">
                {{ $monthName }}
            </h1>
            <flux:button size="sm" variant="subtle" wire:click="nextMonth" icon="chevron-right" />
            <flux:button size="xs" variant="subtle" wire:click="goToToday">
                {{ __('Today') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            {{-- Entity type filter --}}
            <flux:select wire:model.live="filterType" size="sm" class="w-32">
                <option value="all">{{ __('All types') }}</option>
                <option value="diary">{{ __('Diary') }}</option>
                <option value="note">{{ __('Notes') }}</option>
                <option value="postit">{{ __('Post-its') }}</option>
            </flux:select>

            {{-- Tag filter --}}
            <flux:select wire:model.live="filterTag" size="sm" class="w-36">
                <option value="">{{ __('All tags') }}</option>
                @foreach ($userTags as $tag)
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="flex-1 overflow-auto">
        {{-- Day headers --}}
        <div class="calendar-grid mb-1">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div class="py-2 text-center text-xs font-medium uppercase tracking-wide text-[var(--theme-text-muted)]">
                    {{ __($dayName) }}
                </div>
            @endforeach
        </div>

        {{-- Day cells --}}
        <div class="calendar-grid">
            @foreach ($calendarDays as $cell)
                <button
                    wire:click="selectDate('{{ $cell['date'] }}')"
                    @class([
                        'calendar-day group relative flex flex-col items-start rounded-lg border p-1.5 text-left transition-all',
                        'border-[var(--theme-border)] hover:border-[var(--theme-accent)]' => $cell['inMonth'],
                        'border-transparent opacity-40' => !$cell['inMonth'],
                        'calendar-day--today' => $cell['isToday'],
                        'calendar-day--selected ring-2 ring-[var(--theme-accent)]' => $selectedDate === $cell['date'],
                    ])
                >
                    <span @class([
                        'mb-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium',
                        'bg-[var(--theme-accent)] text-white' => $cell['isToday'],
                        'text-[var(--theme-text)]' => $cell['inMonth'] && !$cell['isToday'],
                        'text-[var(--theme-text-muted)]' => !$cell['inMonth'],
                    ])>
                        {{ $cell['day'] }}
                    </span>

                    {{-- Entity dots --}}
                    @if ($cell['entities']->isNotEmpty())
                        <div class="flex flex-wrap gap-0.5">
                            @foreach ($cell['entities']->take(6) as $entity)
                                <span
                                    class="calendar-dot mood-{{ $entity['mood'] }}"
                                    title="{{ ucfirst($entity['type']) }}: {{ $entity['title'] }}"
                                ></span>
                            @endforeach
                            @if ($cell['entities']->count() > 6)
                                <span class="text-[0.6rem] leading-none text-[var(--theme-text-muted)]">
                                    +{{ $cell['entities']->count() - 6 }}
                                </span>
                            @endif
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Selected Day Panel --}}
    @if ($selectedDate !== '')
        <div class="calendar-day-panel rounded-xl border border-[var(--theme-border)] p-4"
             style="background: color-mix(in srgb, var(--theme-bg-secondary) 85%, transparent);">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[var(--theme-text)]">
                    {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, j F Y') }}
                </h2>
                <flux:button size="xs" variant="subtle" wire:click="selectDate('{{ $selectedDate }}')" icon="x-mark" />
            </div>

            @if ($selectedDayEntities->isEmpty())
                <p class="py-6 text-center text-sm text-[var(--theme-text-muted)]">
                    {{ __('No entries for this day.') }}
                </p>
            @else
                <div class="flex flex-col gap-2">
                    @foreach ($selectedDayEntities as $entity)
                        <div @class([
                            'calendar-entity flex items-start gap-3 rounded-lg border border-[var(--theme-border)] p-3 transition-colors hover:border-[var(--theme-accent)]',
                        ]) style="background: color-mix(in srgb, var(--theme-bg) 90%, transparent);">
                            {{-- Type icon --}}
                            <div class="mt-0.5 flex-shrink-0">
                                @if ($entity['type'] === 'diary')
                                    <flux:icon name="book-open" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                                @elseif ($entity['type'] === 'note')
                                    <flux:icon name="document-text" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                                @else
                                    <flux:icon name="clipboard" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="calendar-dot mood-{{ $entity['mood'] }}"></span>
                                    <span class="text-sm font-medium text-[var(--theme-text)]">
                                        {{ $entity['title'] }}
                                    </span>
                                    <span class="text-xs text-[var(--theme-text-muted)]">
                                        {{ $entity['created_at']->format('H:i') }}
                                    </span>
                                </div>
                                @if ($entity['preview'])
                                    <p class="mt-1 text-xs leading-relaxed text-[var(--theme-text-muted)]">
                                        {{ $entity['preview'] }}
                                    </p>
                                @endif
                            </div>

                            {{-- Mood badge --}}
                            <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-medium capitalize mood-{{ $entity['mood'] }}-badge">
                                {{ $entity['type'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
