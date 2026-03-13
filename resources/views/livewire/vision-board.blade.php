<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex min-w-0 items-center gap-2 overflow-hidden border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:button size="sm" icon="plus" x-on:click="$refs.vbImageInput.click()">
            {{ __('Upload Image') }}
        </flux:button>

        <input type="file"
               x-ref="vbImageInput"
               accept="image/jpeg,image/png,image/gif,image/webp"
               class="hidden"
               wire:model="imageUpload" />

        <flux:spacer />

        {{-- Search --}}
        <div x-data="visionBoardSearch" class="flex items-center gap-2">
            <div class="relative">
                <input type="text"
                       x-model="searchQuery"
                       x-on:input.debounce.250ms="filterCards()"
                       placeholder="{{ __('Search images...') }}"
                       class="w-28 rounded-lg border border-zinc-200 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 lg:w-36">
                <button x-show="searchQuery !== '' || activeTagFilter !== null"
                        x-on:click="clearFilters()"
                        x-cloak
                        type="button"
                        class="absolute inset-y-0 right-1 flex items-center px-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                        title="{{ __('Clear filters') }}">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Tag filter --}}
            <div class="relative" x-data="{ tagFilterOpen: false }">
                <button type="button"
                        x-on:click="tagFilterOpen = !tagFilterOpen"
                        :class="activeTagFilter ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Filter by Tag') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
                </button>
                <div x-show="tagFilterOpen"
                     x-on:click.away="tagFilterOpen = false"
                     x-cloak
                     class="absolute right-0 z-50 mt-1 max-h-48 w-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button"
                            x-on:click="filterByTag(null); tagFilterOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            :class="!activeTagFilter ? 'font-semibold' : ''">
                        {{ __('All Tags') }}
                    </button>
                    @foreach($filterAvailableTags as $tag)
                        <button type="button"
                                x-on:click="filterByTag('{{ $tag['id'] }}'); tagFilterOpen = false"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                :class="activeTagFilter === '{{ $tag['id'] }}' ? 'font-semibold' : ''">
                            {{ $tag['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <span class="h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600"></span>

        {{-- Zoom --}}
        <div x-data="visionBoardZoom" class="flex shrink-0 items-center gap-2">
            <flux:button size="sm" icon="minus" x-on:click="zoomOut" />
            <span class="w-12 text-center text-sm text-zinc-600 dark:text-zinc-400"
                  x-text="Math.round(zoom * 100) + '%'"></span>
            <flux:button size="sm" icon="plus" x-on:click="zoomIn" />
        </div>
    </div>

    {{-- Canvas --}}
    <div x-data="visionBoardViewport"
         x-on:scroll.passive="updateScroll()"
         class="flex-1 overflow-auto bg-zinc-900/50 dark:bg-zinc-950/60"
         style="cursor: grab;">

        <div wire:ignore
             x-data="{ get zoom() { return Alpine.store('visionBoard').zoom } }"
             :style="'transform: scale(' + zoom + '); transform-origin: 0 0; width: 4000px; height: 4000px;'"
             class="relative"
             id="vb-canvas"
             x-on:contextmenu.prevent="$dispatch('vb-context', { x: $event.clientX, y: $event.clientY })">

            {{-- Guide lines --}}
            <div id="vb-guides" class="pointer-events-none absolute inset-0" style="z-index: 99998;"></div>

            @foreach($cards as $card)
                <div wire:key="vb-card-{{ $card['id'] }}"
                     data-card-id="{{ $card['id'] }}"
                     data-card-type="image"
                     data-card-tags="{{ implode(',', $card['tag_ids'] ?? []) }}"
                     x-data="visionBoardCard({{ Js::from(array_merge($card, ['is_owner' => $card['owner_id'] === auth()->id()])) }})"
                     x-init="initDrag()"
                     :style="'position: absolute; left: ' + cardX + 'px; top: ' + cardY + 'px; z-index: ' + cardZ + ';{{ $card['color_override'] ? ' background-color: ' . $card['color_override'] . ';' : '' }}' + (cardW ? ' width: ' + cardW + 'px;' : '') + (cardH ? ' height: ' + cardH + 'px;' : '')"
                     x-on:contextmenu.prevent.stop="$dispatch('vb-context', {
                         x: $event.clientX,
                         y: $event.clientY,
                         entityId: '{{ $card['id'] }}',
                         isOwner: {{ $card['owner_id'] === auth()->id() ? 'true' : 'false' }},
                         isPublic: {{ $card['is_public'] ? 'true' : 'false' }},
                         mood: '{{ $card['mood'] ?? 'plain' }}'
                     })"
                     class="vb-card {{ $card['mood'] ? 'mood-' . $card['mood'] : 'mood-plain' }} touch-none select-none">
                    @if(!empty($card['image_url']))
                        <img src="{{ $card['image_url'] }}" alt="{{ $card['title'] }}" class="size-full rounded-lg object-cover" loading="lazy" />
                    @else
                        <div class="flex size-full items-center justify-center rounded-lg bg-zinc-200 text-zinc-400 dark:bg-zinc-700">
                            <svg class="size-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                        </div>
                    @endif
                    {{-- Alt text overlay --}}
                    @if($card['title'])
                        <div class="vb-card-label">{{ $card['title'] }}</div>
                    @endif
                    {{-- Relationship badge --}}
                    @if(($card['siblings_count'] ?? 0) > 0)
                        <div class="vb-card-links" title="{{ trans_choice(':count link|:count links', $card['siblings_count']) }}">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.69" /></svg>
                            {{ $card['siblings_count'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Context menu --}}
    <template x-teleport="body">
        <div x-data="visionBoardContextMenu"
             x-show="open"
             x-on:vb-context.window="handleContext($event.detail)"
             x-on:click.away="close()"
             x-on:keydown.escape.window="close()"
             x-cloak
             :style="'position: fixed; left: ' + x + 'px; top: ' + y + 'px; z-index: 99999;'"
             class="min-w-44 rounded-lg border border-zinc-200 bg-white py-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <template x-if="entityId && isOwner">
                <div>
                    <button x-on:click="edit()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        {{ __('Edit') }}
                    </button>
                    <button x-on:click="linkTo()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.69" /></svg>
                        {{ __('Link to Entry/Note') }}
                    </button>

                    {{-- Mood submenu --}}
                    <div class="relative" x-data="{ moodOpen: false }">
                        <button x-on:click.stop="moodOpen = !moodOpen"
                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span class="flex items-center gap-2">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" /></svg>
                                {{ __('Mood') }}
                            </span>
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                        <div x-show="moodOpen" x-cloak
                             class="absolute left-full top-0 ml-1 min-w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach(\App\Enums\Mood::cases() as $m)
                                @if($m !== \App\Enums\Mood::Custom)
                                    <button x-on:click="changeMood('{{ $m->value }}')"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="mood-{{ $m->value }} inline-block size-3 rounded-full border"></span>
                                        {{ ucfirst($m->value) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <button x-on:click="togglePublic()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9 9 0 0 1 3 12c0-1.47.353-2.856.978-4.082" /></svg>
                        <span x-text="isPublic ? '{{ __('Make Private') }}' : '{{ __('Make Public') }}'"></span>
                    </button>

                    <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

                    <button x-on:click="deleteImage()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                        {{ __('Delete') }}
                    </button>
                </div>
            </template>

            <template x-if="!entityId">
                <div class="px-3 py-1.5 text-sm text-zinc-400">
                    {{ __('Vision Board') }}
                </div>
            </template>
        </div>
    </template>

    {{-- Editor Modal --}}
    @if($showEditorModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-on:keydown.escape.window="$wire.set('showEditorModal', false)">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                 x-on:click.away="$wire.set('showEditorModal', false)">
                <h2 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Edit Image') }}</h2>

                {{-- Alt text --}}
                <div class="mb-3">
                    <flux:input wire:model="editorAlt" :label="__('Description')" placeholder="{{ __('Image description...') }}" />
                </div>

                {{-- Mood selector --}}
                <div class="mb-3">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Mood') }}</label>
                    <div class="flex flex-wrap gap-1">
                        @foreach(\App\Enums\Mood::cases() as $m)
                            <button type="button"
                                    wire:click="$set('editorMood', '{{ $m->value }}')"
                                    class="mood-{{ $m->value }} rounded-full border px-3 py-1 text-xs transition-all {{ $editorMood === $m->value ? 'ring-2 ring-offset-1 ring-blue-400' : '' }}">
                                {{ ucfirst($m->value) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Color override (only for custom) --}}
                @if($editorMood === 'custom')
                    <div class="mb-3">
                        <flux:input type="color" wire:model="editorColorOverride" :label="__('Custom Color')" />
                    </div>
                @endif

                {{-- Tags --}}
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tags') }}</label>
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach($availableTags as $tag)
                            <button type="button"
                                    wire:click="toggleTag('{{ $tag['id'] }}')"
                                    class="rounded-full border px-2 py-0.5 text-xs transition-all {{ in_array($tag['id'], $editorTagIds, true) ? 'bg-blue-100 border-blue-300 dark:bg-blue-900 dark:border-blue-700' : 'border-zinc-200 dark:border-zinc-700' }}">
                                {{ $tag['name'] }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text"
                               wire:model="tagSearch"
                               wire:keydown.enter.prevent="addTag"
                               placeholder="{{ __('New tag...') }}"
                               class="flex-1 rounded-lg border border-zinc-200 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <flux:button size="sm" wire:click="addTag">{{ __('Add') }}</flux:button>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-2">
                    <flux:button size="sm" wire:click="$set('showEditorModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button size="sm" variant="primary" wire:click="saveEditor">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Link Search Modal --}}
    @if($showLinkSearchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-on:keydown.escape.window="$wire.cancelLinkSearch()">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                 x-on:click.away="$wire.cancelLinkSearch()">
                <h2 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Link to Diary Entry or Note') }}</h2>

                <flux:input wire:model.live.debounce.300ms="linkSearchQuery" placeholder="{{ __('Search by title or content...') }}" autofocus />

                @if(count($linkSearchResults) > 0)
                    <div class="mt-3 max-h-60 space-y-1 overflow-y-auto">
                        @foreach($linkSearchResults as $result)
                            <button type="button"
                                    wire:click="linkToEntity('{{ $result['id'] }}', '{{ $result['type'] }}')"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                <span class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-[0.625rem] font-semibold uppercase dark:bg-zinc-700">
                                    {{ $result['type'] === 'diary_entry' ? __('Diary') : __('Note') }}
                                </span>
                                <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $result['title'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif(mb_strlen($linkSearchQuery) >= 2)
                    <p class="mt-3 text-sm text-zinc-400">{{ __('No results found.') }}</p>
                @endif

                <div class="mt-4 flex justify-end">
                    <flux:button size="sm" wire:click="cancelLinkSearch">{{ __('Cancel') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Trashcan --}}
    <div id="vb-trashcan" class="desktop-trashcan">
        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        <span class="text-[0.6rem] font-medium uppercase tracking-wide">{{ __('Delete') }}</span>
    </div>

    {{-- Upload overlay --}}
    <div wire:loading.flex wire:target="imageUpload, uploadImage"
         class="fixed inset-0 z-[99999] items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
            <svg class="size-10 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-white">{{ __('Uploading image...') }}</span>
        </div>
    </div>
</div>
