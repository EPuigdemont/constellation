<div class="flex h-screen flex-col overflow-hidden"
     x-data="{ syncNarrowView() { $wire.setNarrowView(window.matchMedia('(max-width: 767px)').matches); } }"
     x-init="syncNarrowView()"
     x-on:resize.window.debounce.150ms="syncNarrowView()">
    @if ($limitError !== '')
        <div class="border-b border-[var(--theme-border)] px-3 py-2 text-sm text-[var(--theme-text)]"
             style="background: color-mix(in srgb, var(--theme-accent) 12%, var(--theme-bg));">
            {{ $limitError }}
        </div>
    @endif
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-header-bg,theme(colors.zinc.50))] px-2 py-1.5 dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-header-bg,theme(colors.zinc.900))]">
        <flux:heading size="lg" class="max-lg:hidden">{{ __('Diary') }}</flux:heading>

        <flux:spacer />

        {{-- Search --}}
        <div class="relative w-36 lg:w-52">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('Search entries...') }}"
                   class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
            @if($search)
                <button wire:click="$set('search', '')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <x-icons.close class="size-3.5" />
                </button>
            @endif
        </div>

        <flux:button size="sm" icon="plus" wire:click="openNewEntry" title="{{ __('New Entry') }}" aria-label="{{ __('New Entry') }}">
            <x-icons.diary class="hidden size-4 max-[420px]:inline-block" />
            <span class="max-[420px]:hidden">{{ __('New Entry') }}</span>
        </flux:button>

        <flux:button size="sm"
                     x-on:click="$wire.toggleDisplayMode()"
                     :icon="$displayMode === 'scroll' ? 'book-open' : 'bars-3'"
                     title="{{ $displayMode === 'scroll' ? __('Paginated') : __('Scroll') }}"
                     aria-label="{{ $displayMode === 'scroll' ? __('Paginated') : __('Scroll') }}">
            <span class="max-[420px]:hidden">{{ $displayMode === 'scroll' ? __('Paginated') : __('Scroll') }}</span>
        </flux:button>
    </div>

    {{-- Uplifting entry suggestion (sad entry detection) --}}
    @if ($upliftTitle)
        <div class="border-b border-[var(--theme-accent)]/20 px-4 py-3"
             style="background: color-mix(in srgb, var(--theme-accent) 8%, var(--theme-bg));">
            <div class="mx-auto flex max-w-3xl items-start gap-3">
                <span class="mt-0.5 text-lg">💛</span>
                <div class="flex-1">
                    <p class="text-sm font-medium text-[var(--theme-text)]">
                        {{ __('Remember this moment?') }}
                    </p>
                    <p class="text-sm font-semibold text-[var(--theme-accent)]">{{ $upliftTitle }}</p>
                    <p class="mt-0.5 text-xs text-[var(--theme-text-muted)]">{{ $upliftPreview }}</p>
                </div>
                <button wire:click="dismissUplift" class="text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]">
                    <x-icons.close />
                </button>
            </div>
        </div>
    @endif

    {{-- New Entry Form --}}
    @if($showNewEntryForm)
        <div class="border-b border-zinc-200 bg-white px-2 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto max-w-3xl space-y-3">
                <flux:input wire:model="newTitle" placeholder="{{ __('Title...') }}" />
                @error('newTitle') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                <flux:textarea wire:model="newBody" placeholder="{{ __('Write your diary entry...') }}" rows="4" />
                @error('newBody') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror

                {{-- Tags --}}
                <div x-data="{ newTagDropdownOpen: false }" class="relative">
                    <div class="mb-1 flex flex-wrap gap-1">
                        @foreach($availableTags as $tag)
                            @if(in_array($tag['id'], $newTagIds, true))
                                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2 py-0.5 text-xs dark:bg-zinc-700">
                                    {{ $tag['name'] }}
                                    <button type="button" wire:click="toggleNewTag('{{ $tag['id'] }}')" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">&times;</button>
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="newTagSearch"
                           x-on:focus="newTagDropdownOpen = true"
                           x-on:click.away="newTagDropdownOpen = false"
                           placeholder="{{ __('Search or create tag...') }}"
                           class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div x-show="newTagDropdownOpen" x-cloak class="absolute z-50 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        @php
                            $filteredNewTags = collect($availableTags)->filter(fn ($tag) => $newTagSearch === '' || str_contains(strtolower($tag['name']), strtolower($newTagSearch)));
                        @endphp
                        @forelse($filteredNewTags as $tag)
                            <button type="button" wire:click="toggleNewTag('{{ $tag['id'] }}')" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                @if(in_array($tag['id'], $newTagIds, true))
                                    <x-icons.check class="size-4 text-green-500" />
                                @else
                                    <span class="size-4"></span>
                                @endif
                                {{ $tag['name'] }}
                            </button>
                        @empty
                            @if($newTagSearch !== '')
                                <button type="button" wire:click="createNewTagInline('{{ addslashes($newTagSearch) }}')" x-on:click="newTagDropdownOpen = false" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:text-blue-400 dark:hover:bg-zinc-800">
                                    + {{ __('Create') }} "{{ $newTagSearch }}"
                                </button>
                            @endif
                        @endforelse
                        @if($newTagSearch !== '' && $filteredNewTags->isNotEmpty() && !$filteredNewTags->contains(fn ($t) => strtolower($t['name']) === strtolower($newTagSearch)))
                            <button type="button" wire:click="createNewTagInline('{{ addslashes($newTagSearch) }}')" x-on:click="newTagDropdownOpen = false" class="flex w-full items-center gap-2 border-t border-zinc-200 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-800">
                                + {{ __('Create') }} "{{ $newTagSearch }}"
                            </button>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="primary" wire:click="createEntry" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createEntry">{{ __('Save') }}</span>
                        <span wire:loading wire:target="createEntry">…</span>
                    </flux:button>
                    <flux:button size="sm" wire:click="cancelNewEntry">{{ __('Cancel') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Content --}}
    @if($displayMode === 'paginated')
        {{-- Paginated notebook spread --}}
        <div class="diary-spread relative flex flex-1 flex-col overflow-hidden"
             x-data="{ turning: false, direction: '' }"
             x-on:page-turn.window="turning = true; direction = $event.detail.direction; setTimeout(() => turning = false, 400)">
            {{-- Left edge arrow --}}
            @if($currentPage > 1)
                <button wire:click="previousPage"
                        x-on:click="$dispatch('page-turn', { direction: 'left' })"
                        class="diary-page-arrow diary-page-arrow-left group absolute left-0 top-1/2 z-10 flex h-16 w-8 -translate-y-1/2 items-center justify-center rounded-r-lg bg-black/5 opacity-0 transition-all hover:w-10 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10">
                    <x-icons.chevron-right class="size-5 rotate-180 text-zinc-500 transition-transform group-hover:-translate-x-0.5 dark:text-zinc-400" />
                </button>
            @endif

            {{-- Right edge arrow --}}
            @if($currentPage < $totalPages)
                <button wire:click="nextPage"
                        x-on:click="$dispatch('page-turn', { direction: 'right' })"
                        class="diary-page-arrow diary-page-arrow-right group absolute right-0 top-1/2 z-10 flex h-16 w-8 -translate-y-1/2 items-center justify-center rounded-l-lg bg-black/5 opacity-0 transition-all hover:w-10 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10">
                    <x-icons.chevron-right class="size-5 text-zinc-500 transition-transform group-hover:translate-x-0.5 dark:text-zinc-400" />
                </button>
            @endif

            <div class="diary-pages diary-pages-responsive mx-auto flex flex-1 w-full max-w-5xl items-stretch gap-4 overflow-hidden px-0"
                 :class="{ 'diary-turn-left': turning && direction === 'left', 'diary-turn-right': turning && direction === 'right' }">
                @forelse($entries as $entry)
                    @php $moodClass = $entry->mood ? 'mood-' . $entry->mood->value : ''; @endphp
                    <div class="diary-entry-themed {{ $moodClass }} {{ $editingEntryId === $entry->id ? 'is-editing' : '' }} flex flex-1 flex-col rounded-lg border p-6 shadow-md"
                         style="border-color: var(--card-border, var(--theme-border, theme(colors.zinc.200)));"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        <canvas class="diary-entry-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>
                        @if($editingEntryId === $entry->id)
                            <div class="relative z-[3] flex flex-1 flex-col gap-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                @error('editTitle') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" class="flex-1" rows="10" />
                                @error('editBody') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                                @include('livewire.partials.diary-tag-editor', ['tagIds' => $editTagIds, 'searchProp' => 'tagSearch', 'toggleMethod' => 'toggleEditTag', 'createMethod' => 'createEditTagInline'])
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveEntry">{{ __('Save') }}</span>
                                        <span wire:loading wire:target="saveEntry">…</span>
                                    </flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="relative z-[3] mb-3 flex items-center justify-between">
                                <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    {{ $entry->created_at?->translatedFormat('l, j F Y H:i') }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if($entry->mood)
                                        <flux:select size="sm" class="!w-28 !text-xs" wire:change="changeMood('{{ $entry->id }}', $event.target.value)">
                                            @foreach(\App\Enums\Mood::cases() as $mood)
                                                <option value="{{ $mood->value }}" @selected($entry->mood === $mood)>{{ ucfirst($mood->value) }}</option>
                                            @endforeach
                                        </flux:select>
                                    @endif
                                </div>
                            </div>
                            @if($entry->title)
                                <h2 class="diary-entry-title relative z-[3] text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ $entry->title }}</h2>
                            @endif
                            <div class="diary-entry-body relative z-[3] flex-1 overflow-y-auto">
                                <div class="tiptap-editor-content prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300">
                                    {!! $entry->body !!}
                                </div>
                            </div>
                            @if($entry->tags->isNotEmpty())
                                <div class="relative z-[3] mt-2 flex flex-wrap gap-1">
                                    @foreach($entry->tags as $tag)
                                        <span class="rounded-full bg-zinc-200/60 px-2 py-0.5 text-[0.65rem] text-zinc-600 dark:bg-zinc-700/60 dark:text-zinc-400">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <p class="relative z-[3] mt-3 text-[0.65rem] italic text-zinc-400">{{ __('Double-click to edit') }}</p>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-1 items-center justify-center text-zinc-400">
                        @if($search)
                            {{ __('No entries match your search.') }}
                        @else
                            {{ __('No diary entries yet.') }}
                        @endif
                    </div>
                @endforelse
            </div>

            @if($totalPages > 1)
                <div class="flex shrink-0 items-center justify-center gap-4 border-t border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-header-bg,theme(colors.zinc.50))] px-2 py-1 dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-header-bg,theme(colors.zinc.900))]">
                    <flux:button size="sm" wire:click="previousPage" :disabled="$currentPage <= 1" icon="chevron-left">
                        {{ __('Previous') }}
                    </flux:button>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Page :current of :total', ['current' => $currentPage, 'total' => $totalPages]) }}
                    </span>
                    <flux:button size="sm" wire:click="nextPage" :disabled="$currentPage >= $totalPages" icon-trailing="chevron-right">
                        {{ __('Next') }}
                    </flux:button>
                </div>
            @endif
        </div>
    @else
        {{-- Scroll mode --}}
        <div class="flex-1 overflow-y-auto">
            <div class="mx-auto max-w-3xl space-y-4 px-0 py-0">
                @forelse($allEntries as $entry)
                    @php $moodClass = $entry->mood ? 'mood-' . $entry->mood->value : ''; @endphp
                    <div class="diary-entry-themed {{ $moodClass }} {{ $editingEntryId === $entry->id ? 'is-editing' : '' }} rounded-lg border p-6 shadow-md"
                         style="border-color: var(--card-border, var(--theme-border, theme(colors.zinc.200)));"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        <canvas class="diary-entry-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>
                        @if($editingEntryId === $entry->id)
                            <div class="relative z-[3] space-y-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                @error('editTitle') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" rows="6" />
                                @error('editBody') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                                @include('livewire.partials.diary-tag-editor', ['tagIds' => $editTagIds, 'searchProp' => 'tagSearch', 'toggleMethod' => 'toggleEditTag', 'createMethod' => 'createEditTagInline'])
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveEntry">{{ __('Save') }}</span>
                                        <span wire:loading wire:target="saveEntry">…</span>
                                    </flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="relative z-[3] mb-3 flex items-center justify-between">
                                <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    {{ $entry->created_at?->translatedFormat('l, j F Y H:i') }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if($entry->mood)
                                        <flux:select size="sm" class="!w-28 !text-xs" wire:change="changeMood('{{ $entry->id }}', $event.target.value)">
                                            @foreach(\App\Enums\Mood::cases() as $mood)
                                                <option value="{{ $mood->value }}" @selected($entry->mood === $mood)>{{ ucfirst($mood->value) }}</option>
                                            @endforeach
                                        </flux:select>
                                    @endif
                                </div>
                            </div>
                            @if($entry->title)
                                <h2 class="diary-entry-title relative z-[3] text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ $entry->title }}</h2>
                            @endif
                            <div class="diary-entry-body relative z-[3]">
                                <div class="tiptap-editor-content prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300">
                                    {!! $entry->body !!}
                                </div>
                            </div>
                            @if($entry->tags->isNotEmpty())
                                <div class="relative z-[3] mt-2 flex flex-wrap gap-1">
                                    @foreach($entry->tags as $tag)
                                        <span class="rounded-full bg-zinc-200/60 px-2 py-0.5 text-[0.65rem] text-zinc-600 dark:bg-zinc-700/60 dark:text-zinc-400">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <p class="relative z-[3] mt-3 text-[0.65rem] italic text-zinc-400">{{ __('Double-click to edit') }}</p>
                        @endif
                    </div>
                @empty
                    <div class="flex items-center justify-center py-20 text-zinc-400">
                        @if($search)
                            {{ __('No entries match your search.') }}
                        @else
                            {{ __('No diary entries yet. Click "+ New Entry" to create one.') }}
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
