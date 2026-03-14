<div class="page-glitter-wrapper flex h-full flex-col gap-4 p-4 lg:p-6">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->theme ?? 'summer' }}"></canvas>
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
                <option value="reminder">{{ __('Reminders') }}</option>
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
                                @if ($entity['type'] === 'important_date')
                                    <span class="calendar-dot-star" title="{{ $entity['title'] }}">★</span>
                                @elseif ($entity['type'] === 'reminder')
                                    <span class="calendar-dot-bell" title="{{ $entity['title'] }}">🔔</span>
                                @else
                                    <span
                                        class="calendar-dot mood-{{ $entity['mood'] }}"
                                        title="{{ ucfirst($entity['type']) }}: {{ $entity['title'] }}"
                                    ></span>
                                @endif
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
                <div class="flex items-center gap-1">
                    {{-- Add entry button with dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <flux:button size="xs" variant="subtle" icon="plus" x-on:click="open = !open" />
                        <div x-show="open" x-cloak x-on:click.outside="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 top-full z-20 mt-1 min-w-[10rem] rounded-lg border border-[var(--theme-border)] p-1 shadow-lg"
                             style="background: var(--theme-bg);">
                            <button type="button"
                                    wire:click="openCreateForm('diary')"
                                    x-on:click="open = false"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                                <flux:icon name="book-open" variant="outline" class="size-4" />
                                {{ __('Diary Entry') }}
                            </button>
                            <button type="button"
                                    wire:click="openCreateForm('note')"
                                    x-on:click="open = false"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                                <flux:icon name="document-text" variant="outline" class="size-4" />
                                {{ __('Note') }}
                            </button>
                            <button type="button"
                                    wire:click="openCreateForm('postit')"
                                    x-on:click="open = false"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                                <flux:icon name="clipboard" variant="outline" class="size-4" />
                                {{ __('Post-it') }}
                            </button>
                            <button type="button"
                                    wire:click="openCreateForm('reminder')"
                                    x-on:click="open = false"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                                <flux:icon name="bell" variant="outline" class="size-4" />
                                {{ __('Reminder') }}
                            </button>
                        </div>
                    </div>
                    <flux:button size="xs" variant="subtle" wire:click="selectDate('{{ $selectedDate }}')" icon="x-mark" />
                </div>
            </div>

            {{-- Quick Create Form --}}
            @if ($showCreateForm)
                <div class="mb-3 rounded-lg border border-[var(--theme-accent)]/30 p-3"
                     style="background: color-mix(in srgb, var(--theme-bg) 95%, var(--theme-accent));">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-[var(--theme-accent)]">
                            {{ __('New') }} {{ __($createType === 'diary' ? 'Diary Entry' : ($createType === 'note' ? 'Note' : ($createType === 'reminder' ? 'Reminder' : 'Post-it'))) }}
                        </span>
                    </div>

                    @if ($createType !== 'postit')
                        <input type="text"
                               wire:model="createTitle"
                               placeholder="{{ __('Title') }}"
                               class="mb-2 w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-1.5 text-sm text-[var(--theme-text)] placeholder-[var(--theme-text-muted)] focus:border-[var(--theme-accent)] focus:outline-none" />
                    @endif

                    <textarea wire:model="createBody"
                              placeholder="{{ __('Write something...') }}"
                              rows="3"
                              class="mb-2 w-full resize-none rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-1.5 text-sm text-[var(--theme-text)] placeholder-[var(--theme-text-muted)] focus:border-[var(--theme-accent)] focus:outline-none"></textarea>

                    <div class="flex items-center justify-end gap-2">
                        <flux:button size="xs" variant="subtle" wire:click="closeCreateForm">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button size="xs" variant="primary" wire:click="saveNewEntity">
                            {{ __('Save') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            @if ($selectedDayEntities->isEmpty() && !$showCreateForm)
                <p class="py-6 text-center text-sm text-[var(--theme-text-muted)]">
                    {{ __('No entries for this day.') }}
                </p>
            @elseif ($selectedDayEntities->isNotEmpty())
                <div class="flex flex-col gap-2">
                    @foreach ($selectedDayEntities as $entity)
                        <div wire:dblclick="openEntityModal('{{ $entity['type'] }}', '{{ $entity['id'] }}')"
                             @class([
                                'calendar-entity flex cursor-pointer items-start gap-3 rounded-lg border border-[var(--theme-border)] p-3 transition-colors hover:border-[var(--theme-accent)]',
                             ])
                             style="background: color-mix(in srgb, var(--theme-bg) 90%, transparent);"
                             title="{{ __('Double-click to view full content') }}">
                            {{-- Type icon --}}
                            <div class="mt-0.5 flex-shrink-0">
                                @if ($entity['type'] === 'diary')
                                    <flux:icon name="book-open" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                                @elseif ($entity['type'] === 'note')
                                    <flux:icon name="document-text" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                                @elseif ($entity['type'] === 'important_date')
                                    <flux:icon name="star" variant="solid" class="size-4 text-[var(--theme-accent)]" />
                                @elseif ($entity['type'] === 'reminder')
                                    <flux:icon name="bell" variant="solid" class="size-4 text-[var(--theme-accent)]" />
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

    {{-- Entity Detail Modal --}}
    @if ($showEntityModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeEntityModal()">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50" wire:click="closeEntityModal"></div>

            {{-- Modal --}}
            <div class="relative z-10 flex max-h-[80vh] w-full max-w-2xl flex-col rounded-xl border border-[var(--theme-border)] shadow-2xl"
                 style="background: var(--theme-bg);">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-[var(--theme-border)] px-5 py-3">
                    <div class="flex items-center gap-3">
                        @if ($modalEntityType === 'diary')
                            <flux:icon name="book-open" variant="outline" class="size-5 text-[var(--theme-accent)]" />
                        @elseif ($modalEntityType === 'note')
                            <flux:icon name="document-text" variant="outline" class="size-5 text-[var(--theme-accent)]" />
                        @else
                            <flux:icon name="clipboard" variant="outline" class="size-5 text-[var(--theme-accent)]" />
                        @endif
                        <div>
                            <h3 class="text-base font-semibold text-[var(--theme-text)]">{{ $modalEntityTitle }}</h3>
                            <div class="flex items-center gap-2 text-xs text-[var(--theme-text-muted)]">
                                <span class="calendar-dot mood-{{ $modalEntityMood }}"></span>
                                <span class="capitalize">{{ $modalEntityType }}</span>
                                <span>&middot;</span>
                                <span>{{ $modalEntityTime }}</span>
                            </div>
                        </div>
                    </div>
                    <flux:button size="sm" variant="subtle" wire:click="closeEntityModal" icon="x-mark" />
                </div>

                {{-- Body --}}
                <div class="calendar-modal-body flex-1 overflow-y-auto px-5 py-4">
                    <div class="prose prose-sm max-w-none text-[var(--theme-text)]
                                prose-headings:text-[var(--theme-text)]
                                prose-p:text-[var(--theme-text)]
                                prose-strong:text-[var(--theme-text)]
                                prose-a:text-[var(--theme-accent)]">
                        {!! $modalEntityBody !!}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
