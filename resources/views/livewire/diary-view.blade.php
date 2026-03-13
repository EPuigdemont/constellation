<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Diary') }}</flux:heading>

        <flux:spacer />

        <flux:button size="sm"
                     x-on:click="$wire.toggleDisplayMode()"
                     :icon="$displayMode === 'scroll' ? 'book-open' : 'bars-3'">
            {{ $displayMode === 'scroll' ? __('Paginated') : __('Scroll') }}
        </flux:button>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-y-auto bg-zinc-100 dark:bg-zinc-800">
        @if($displayMode === 'paginated')
            {{-- Paginated notebook spread --}}
            <div class="mx-auto flex min-h-full max-w-5xl items-start justify-center gap-6 p-8">
                @forelse($entries as $entry)
                    <div class="diary-page flex-1 rounded-lg border border-zinc-200 bg-white p-6 shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-zinc-400">
                                {{ $entry->created_at?->format('l, j F Y') }}
                            </span>
                            @if($entry->mood)
                                <span class="rounded-full px-2 py-0.5 text-[0.625rem] font-semibold uppercase mood-{{ $entry->mood->value }}"
                                      style="background: var(--card-badge-bg); color: var(--card-badge-text);">
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
                    </div>
                @empty
                    <div class="flex flex-1 items-center justify-center py-20 text-zinc-400">
                        {{ __('No diary entries yet.') }}
                    </div>
                @endforelse
            </div>

            {{-- Pagination controls --}}
            @if($totalPages > 1)
                <div class="flex items-center justify-center gap-4 border-t border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
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
        @else
            {{-- Infinite scroll mode --}}
            <div class="mx-auto max-w-3xl space-y-6 p-8">
                @forelse($allEntries as $entry)
                    <div class="diary-entry rounded-lg border border-zinc-200 bg-white p-6 shadow-md dark:border-zinc-700 dark:bg-zinc-900 {{ $entry->mood ? 'mood-' . $entry->mood->value : '' }}">
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
                    </div>
                @empty
                    <div class="flex items-center justify-center py-20 text-zinc-400">
                        {{ __('No diary entries yet. Create one from the Desktop.') }}
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>
