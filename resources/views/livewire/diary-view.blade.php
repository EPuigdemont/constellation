<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-header-bg,theme(colors.zinc.50))] px-2 py-1.5 dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-header-bg,theme(colors.zinc.900))]">
        <flux:heading size="lg">{{ __('Diary') }}</flux:heading>

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
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            @endif
        </div>

        <flux:button size="sm" icon="plus" wire:click="openNewEntry">
            {{ __('New Entry') }}
        </flux:button>

        <flux:button size="sm"
                     x-on:click="$wire.toggleDisplayMode()"
                     :icon="$displayMode === 'scroll' ? 'book-open' : 'bars-3'">
            {{ $displayMode === 'scroll' ? __('Paginated') : __('Scroll') }}
        </flux:button>
    </div>

    {{-- New Entry Form --}}
    @if($showNewEntryForm)
        <div class="border-b border-zinc-200 bg-white px-2 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto max-w-3xl space-y-3">
                <flux:input wire:model="newTitle" placeholder="{{ __('Title...') }}" />
                <flux:textarea wire:model="newBody" placeholder="{{ __('Write your diary entry...') }}" rows="4" />
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="primary" wire:click="createEntry">{{ __('Save') }}</flux:button>
                    <flux:button size="sm" wire:click="cancelNewEntry">{{ __('Cancel') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Content --}}
    @if($displayMode === 'paginated')
        {{-- Paginated notebook spread --}}
        <div class="flex flex-1 flex-col overflow-hidden bg-[var(--theme-bg-secondary,theme(colors.zinc.100))] dark:bg-[var(--theme-bg-secondary,theme(colors.zinc.800))]">
            <div class="mx-auto flex flex-1 w-full max-w-5xl items-stretch gap-4 overflow-hidden px-0">
                @forelse($entries as $entry)
                    @php $moodClass = $entry->mood ? 'mood-' . $entry->mood->value : ''; @endphp
                    <div class="diary-entry-themed {{ $moodClass }} flex flex-1 flex-col overflow-y-auto rounded-lg border p-6 shadow-md"
                         style="background: var(--card-bg, var(--theme-bg, white)); border-color: var(--card-border, var(--theme-border, theme(colors.zinc.200)));"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        @if($editingEntryId === $entry->id)
                            <div class="flex flex-1 flex-col gap-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" class="flex-1" rows="10" />
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry">{{ __('Save') }}</flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    {{ $entry->created_at?->format('l, j F Y') }}
                                </span>
                                @if($entry->mood)
                                    <span class="desktop-card-badge mood-{{ $entry->mood->value }}">
                                        {{ ucfirst($entry->mood->value) }}
                                    </span>
                                @endif
                            </div>
                            @if($entry->title)
                                <h2 class="mb-2 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ $entry->title }}</h2>
                            @endif
                            <div class="tiptap-editor-content prose prose-sm max-w-none flex-1 text-zinc-700 dark:text-zinc-300">
                                {!! $entry->body !!}
                            </div>
                            <p class="mt-3 text-[0.65rem] italic text-zinc-400">{{ __('Double-click to edit') }}</p>
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
                <div class="flex shrink-0 items-center justify-center gap-4 border-t border-zinc-200 bg-zinc-50 px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
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
        <div class="flex-1 overflow-y-auto bg-[var(--theme-bg-secondary,theme(colors.zinc.100))] dark:bg-[var(--theme-bg-secondary,theme(colors.zinc.800))]">
            <div class="mx-auto max-w-3xl space-y-4 px-0 py-0">
                @forelse($allEntries as $entry)
                    @php $moodClass = $entry->mood ? 'mood-' . $entry->mood->value : ''; @endphp
                    <div class="diary-entry-themed {{ $moodClass }} rounded-lg border p-6 shadow-md"
                         style="background: var(--card-bg, var(--theme-bg, white)); border-color: var(--card-border, var(--theme-border, theme(colors.zinc.200)));"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        @if($editingEntryId === $entry->id)
                            <div class="space-y-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" rows="6" />
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry">{{ __('Save') }}</flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    {{ $entry->created_at?->format('l, j F Y \a\t H:i') }}
                                </span>
                                @if($entry->mood)
                                    <span class="desktop-card-badge mood-{{ $entry->mood->value }}">
                                        {{ ucfirst($entry->mood->value) }}
                                    </span>
                                @endif
                            </div>
                            @if($entry->title)
                                <h2 class="mb-2 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ $entry->title }}</h2>
                            @endif
                            <div class="tiptap-editor-content prose prose-sm max-w-none text-zinc-700 dark:text-zinc-300">
                                {!! $entry->body !!}
                            </div>
                            <p class="mt-3 text-[0.65rem] italic text-zinc-400">{{ __('Double-click to edit') }}</p>
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
