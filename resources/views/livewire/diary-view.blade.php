<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Diary') }}</flux:heading>

        <flux:spacer />

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
        <div class="border-b border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900">
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
        {{-- Paginated notebook spread — flex-1 fills remaining height, no scroll --}}
        <div class="flex flex-1 flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            <div class="mx-auto flex flex-1 w-full max-w-5xl items-stretch gap-6 overflow-hidden p-6">
                @forelse($entries as $entry)
                    <div class="flex flex-1 flex-col overflow-y-auto rounded-lg border border-zinc-200 bg-white p-6 shadow-md dark:border-zinc-700 dark:bg-zinc-900"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        @if($editingEntryId === $entry->id)
                            {{-- Editing mode --}}
                            <div class="flex flex-1 flex-col gap-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" class="flex-1" rows="10" />
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry">{{ __('Save') }}</flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            {{-- Read mode --}}
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
                        {{ __('No diary entries yet.') }}
                    </div>
                @endforelse
            </div>

            {{-- Pagination controls — anchored to bottom --}}
            @if($totalPages > 1)
                <div class="flex shrink-0 items-center justify-center gap-4 border-t border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
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
        {{-- Infinite scroll mode --}}
        <div class="flex-1 overflow-y-auto bg-zinc-100 dark:bg-zinc-800">
            <div class="mx-auto max-w-3xl space-y-6 p-8">
                @forelse($allEntries as $entry)
                    <div class="diary-entry rounded-lg border border-zinc-200 bg-white p-6 shadow-md dark:border-zinc-700 dark:bg-zinc-900 {{ $entry->mood ? 'mood-' . $entry->mood->value : '' }}"
                         @if($editingEntryId !== $entry->id) x-on:dblclick="$wire.startEditing('{{ $entry->id }}')" @endif>
                        @if($editingEntryId === $entry->id)
                            {{-- Editing mode --}}
                            <div class="space-y-3">
                                <flux:input wire:model="editTitle" placeholder="{{ __('Title...') }}" />
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Write...') }}" rows="6" />
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="saveEntry">{{ __('Save') }}</flux:button>
                                    <flux:button size="sm" wire:click="cancelEditing">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            {{-- Read mode --}}
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    {{ $entry->created_at?->format('l, j F Y \a\t H:i') }}
                                </span>
                                @if($entry->mood)
                                    <span class="desktop-card-badge">
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
                        {{ __('No diary entries yet. Click "+ New Entry" to create one.') }}
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
