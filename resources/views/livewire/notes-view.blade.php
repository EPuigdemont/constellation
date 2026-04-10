@php use App\Enums\Mood;use Carbon\Carbon; @endphp

<div class="page-glitter-wrapper flex h-screen flex-col overflow-hidden">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>

    @if ($limitError !== '')
        <div class="border-b border-[var(--theme-border)] px-3 py-2 text-sm text-[var(--theme-text)]"
             style="background: color-mix(in srgb, var(--theme-accent) 12%, var(--theme-bg));">
            {{ $limitError }}
        </div>
    @endif

    <div class="flex items-center gap-3 border-b border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-header-bg,theme(colors.zinc.50))] px-2 py-1.5 dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-header-bg,theme(colors.zinc.900))]">
        <flux:heading size="lg" class="max-lg:hidden">{{ __('Notes') }}</flux:heading>

        <flux:spacer />

        <div class="relative w-40 lg:w-56">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('Search notes...') }}"
                   class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
            @if($search !== '')
                <button wire:click="$set('search', '')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <x-icons.close class="size-3.5" />
                </button>
            @endif
        </div>

        <flux:button size="sm" icon="plus" wire:click="openNoteModal" title="{{ __('New Note') }}" aria-label="{{ __('New Note') }}">
            {{ __('New Note') }}
        </flux:button>
    </div>

    <div class="flex-1 overflow-y-auto p-3 sm:p-4">
        @if($notesByDay->isEmpty())
            <div class="flex h-full items-center justify-center text-sm text-[var(--theme-text-muted)]">
                @if ($search !== '')
                    {{ __('No notes match your search.') }}
                @else
                    {{ __('No notes yet. Click "+ New Note" to create one.') }}
                @endif
            </div>
        @else
            <div class="notes-days-grid grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($notesByDay as $date => $dayNotes)
                    @php
                        $isExpanded = in_array($date, $expandedDays, true);
                        $displayDate = Carbon::parse($date);
                        $stackCount = min(3, $dayNotes->count());
                    @endphp
                    <section wire:key="notes-day-{{ $date }}" @class([
                        'notes-day-card rounded-xl border border-[var(--theme-border)] p-3 transition-all',
                        'notes-day-card-collapsed' => ! $isExpanded,
                        'ring-1 ring-[var(--theme-accent)]' => $isExpanded,
                    ]) style="background: color-mix(in srgb, var(--theme-bg) 90%, transparent);">
                        <header class="mb-3 flex items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-[var(--theme-text)]">{{ $displayDate->translatedFormat('l, j F Y') }}</h2>
                                <p class="text-xs text-[var(--theme-text-muted)]">
                                    {{ __(':count notes', ['count' => $dayNotes->count()]) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <flux:button size="xs" variant="subtle" icon="plus" wire:click="openNoteModal('{{ $date }}')"
                                             title="{{ __('Add note for this day') }}"/>
                                <flux:button size="xs" variant="subtle"
                                             :icon="$isExpanded ? 'arrows-pointing-in' : 'arrows-pointing-out'"
                                             wire:click="toggleDayExpansion('{{ $date }}')"
                                             title="{{ $isExpanded ? __('Collapse') : __('Expand') }}"/>
                            </div>
                        </header>

                        @if($isExpanded)
                            <div class="notes-expanded-grid grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach($dayNotes as $note)
                                    @php
                                        $moodClass = $note->mood ? 'mood-' . $note->mood->value : '';
                                    @endphp
                                    <article wire:key="notes-day-{{ $date }}-note-{{ $note->id }}" class="notes-note-card {{ $moodClass }} cursor-pointer rounded-lg border border-[var(--theme-border)] p-3"
                                             style="border-color: var(--card-border, var(--theme-border));"
                                             wire:dblclick="openEditModal('{{ $note->id }}')"
                                             title="{{ __('Double-click to edit') }}">
                                        <div class="mb-2 flex items-start justify-between gap-2">
                                            <h3 class="line-clamp-2 text-sm font-semibold text-[var(--theme-text)]">{{ $note->title ?: __('Untitled') }}</h3>
                                            <span class="shrink-0 text-[0.65rem] text-[var(--theme-text-muted)]">{{ $note->created_at?->format('H:i') }}</span>
                                        </div>
                                        <p class="line-clamp-4 text-xs leading-relaxed text-[var(--theme-text-muted)]">{{ str(strip_tags($note->body ?? ''))->limit(140) }}</p>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <button type="button"
                                    class="notes-stack-preview notes-stack-preview-collapsed relative block w-full text-left"
                                    style="--stack-count: {{ $stackCount }};"
                                    wire:dblclick="toggleDayExpansion('{{ $date }}')"
                                    title="{{ __('Double-click to expand') }}">
                                @foreach($dayNotes->take(3) as $index => $note)
                                    @php
                                        $moodClass = $note->mood ? 'mood-' . $note->mood->value : '';
                                    @endphp
                                    <div wire:key="notes-day-{{ $date }}-stack-{{ $note->id }}" class="notes-stack-item {{ $moodClass }} absolute inset-x-0 rounded-lg border border-[var(--theme-border)] p-3"
                                         style="top: {{ $index * 14 }}px; z-index: {{ 30 - $index }}; border-color: var(--card-border, var(--theme-border));">
                                        <h3 class="line-clamp-1 text-sm font-semibold text-[var(--theme-text)]">{{ $note->title ?: __('Untitled') }}</h3>
                                        <p class="mt-1 line-clamp-2 text-xs text-[var(--theme-text-muted)]">{{ str(strip_tags($note->body ?? ''))->limit(80) }}</p>
                                    </div>
                                @endforeach

                                @if($dayNotes->count() > 3)
                                    <div class="absolute bottom-0 right-0 rounded-full border border-[var(--theme-border)] px-2 py-0.5 text-xs text-[var(--theme-text-muted)]"
                                         style="background: color-mix(in srgb, var(--theme-bg-secondary) 70%, transparent);">
                                        +{{ $dayNotes->count() - 3 }}
                                    </div>
                                @endif
                            </button>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>

    <flux:modal wire:model="showEditorModal" class="w-full max-w-3xl" flyout>
        <div class="desktop-editor-modal space-y-3 sm:space-y-5"
             :class="'mood-' + ($wire.editorMood || 'plain')"
             :style="$wire.editorColorOverride ? 'background-color: ' + $wire.editorColorOverride : ''">
            <flux:heading size="lg">
                {{ $editingNoteId !== '' ? __('Edit Note') : __('New Note') }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="editorTitle" placeholder="{{ __('Enter title...') }}"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Date') }}</flux:label>
                    <input type="date" wire:model="editorDate"
                           class="w-full rounded-md border border-(--theme-border) bg-(--theme-bg) px-3 py-1.5 text-sm text-(--theme-text) focus:border-(--theme-accent) focus:outline-none"/>
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Content') }}</flux:label>
                <textarea wire:model="editorBody"
                          rows="10"
                          class="w-full rounded-lg border border-(--card-border,var(--color-zinc-200)) bg-(--card-bg,var(--color-zinc-50)) p-3 text-sm text-(--theme-text) dark:border-zinc-700 dark:bg-zinc-900"
                          placeholder="{{ __('Write your note...') }}"></textarea>
            </flux:field>

            <div class="grid grid-cols-1 gap-5 {{ $editorMood === 'custom' ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                <flux:field>
                    <flux:label>{{ __('Mood') }}</flux:label>
                    <flux:select wire:model.live="editorMood">
                        @foreach(Mood::cases() as $mood)
                            <flux:select.option value="{{ $mood->value }}">{{ ucfirst($mood->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                @if($editorMood === 'custom')
                    <flux:field>
                        <flux:label>{{ __('Color') }}</flux:label>
                        <div class="flex items-center gap-2">
                            <input type="color"
                                   wire:model.live="editorColorOverride"
                                   class="h-9 w-12 cursor-pointer rounded border border-zinc-200 dark:border-zinc-700"
                                   title="{{ __('Custom color override') }}">
                            @if($editorColorOverride)
                                <button type="button"
                                        wire:click="$set('editorColorOverride', null)"
                                        class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                                        title="{{ __('Reset color') }}">
                                    {{ __('Reset') }}
                                </button>
                            @endif
                        </div>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>{{ __('Tags') }}</flux:label>
                    <div x-data="{ tagDropdownOpen: false }" class="relative">
                        <div class="mb-1 flex flex-wrap gap-1">
                            @foreach($availableTags as $tag)
                                @if(in_array($tag['id'], $editorTagIds, true))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2 py-0.5 text-xs dark:bg-zinc-700">
                                        {{ $tag['name'] }}
                                        <button type="button" wire:click="toggleTag('{{ $tag['id'] }}')"
                                                class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        <input type="text"
                               wire:model.live.debounce.300ms="tagSearch"
                               x-on:focus="tagDropdownOpen = true"
                               x-on:click.away="tagDropdownOpen = false"
                               placeholder="{{ __('Search or create tag...') }}"
                               class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">

                        <div x-show="tagDropdownOpen" x-cloak
                             class="absolute z-50 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @php
                                $filteredTags = collect($availableTags)->filter(function ($tag) {
                                    if ($this->tagSearch === '') {
                                        return true;
                                    }

                                    return str_contains(strtolower($tag['name']), strtolower($this->tagSearch));
                                });
                            @endphp

                            @forelse($filteredTags as $tag)
                                <button type="button"
                                        wire:click="toggleTag('{{ $tag['id'] }}')"
                                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    @if(in_array($tag['id'], $editorTagIds, true))
                                        <x-icons.check class="size-4 text-green-500" />
                                    @else
                                        <span class="size-4"></span>
                                    @endif
                                    {{ $tag['name'] }}
                                </button>
                            @empty
                                @if($tagSearch !== '')
                                    <button type="button"
                                            wire:click="createTagInline('{{ addslashes($tagSearch) }}')"
                                            x-on:click="tagDropdownOpen = false"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:text-blue-400 dark:hover:bg-zinc-800">
                                        + {{ __('Create') }} "{{ $tagSearch }}"
                                    </button>
                                @else
                                    <div class="px-3 py-1.5 text-sm text-zinc-400">{{ __('No tags available') }}</div>
                                @endif
                            @endforelse
                        </div>
                    </div>
                </flux:field>
            </div>

            <div class="flex items-center justify-between">
                @if($editingNoteId !== '')
                    <div x-data="{ confirmDelete: false }">
                        <flux:button x-show="!confirmDelete" variant="danger" size="sm"
                                     x-on:click="confirmDelete = true">
                            {{ __('Delete') }}
                        </flux:button>
                        <div x-show="confirmDelete" x-cloak class="flex items-center gap-2">
                            <span class="text-sm text-red-600 dark:text-red-400">{{ __('Are you sure?') }}</span>
                            <flux:button variant="danger" size="sm" wire:click="deleteFromEditor">{{ __('Yes, delete') }}</flux:button>
                            <flux:button size="sm" x-on:click="confirmDelete = false">{{ __('No') }}</flux:button>
                        </div>
                    </div>
                @else
                    <div></div>
                @endif

                <div class="flex gap-2">
                    <flux:button wire:click="closeEditor">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" wire:click="saveEditor">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>


