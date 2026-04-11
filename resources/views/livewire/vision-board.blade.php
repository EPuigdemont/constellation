@php use App\Enums\Mood; @endphp
<div class="page-glitter-wrapper flex h-screen flex-col overflow-hidden">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>
    @if ($limitError !== '')
        <div class="relative z-20 border-b border-(--theme-border,var(--color-zinc-200)) px-3 py-2 text-sm text-(--theme-text,var(--color-zinc-900))"
             style="background: color-mix(in srgb, var(--theme-accent) 12%, var(--theme-bg));">
            {{ $limitError }}
        </div>
    @endif
    {{-- Toolbar --}}
    <div x-data="visionBoardToolbar"
         class="relative z-10 border-b border-(--theme-border,var(--color-zinc-200)) bg-(--theme-header-bg,var(--color-zinc-50)) px-2 py-1.5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-header-bg,var(--color-zinc-900)) min-[836px]:flex min-[836px]:items-center min-[836px]:gap-2 min-[836px]:flex-nowrap">
        <div class="flex min-w-0 items-center gap-2 min-[836px]:contents">
            <flux:button size="sm"
                         icon="plus"
                         x-on:click="$wire.requestImageUpload()"
                         wire:loading.attr="disabled"
                         wire:target="imageUpload"
                         title="{{ __('Upload Image') }}"
                         aria-label="{{ __('Upload Image') }}"/>
            <flux:button size="sm"
                         icon="arrow-down-tray"
                         x-on:click="$dispatch('vb-export')"
                         title="{{ __('Export PNG') }}"
                         aria-label="{{ __('Export PNG') }}"/>

            <input type="file"
                   x-ref="vbImageInput"
                   x-on:open-vision-board-image-picker.window="$refs.vbImageInput.click()"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   class="hidden"
                   wire:model="imageUpload"/>

            <flux:spacer class="min-[836px]:hidden"/>

            <flux:button size="sm"
                         icon="bars-2"
                         class="min-[836px]:hidden"
                         x-on:click="toggleControls()"
                         x-bind:aria-expanded="controlsOpen"
                         x-bind:title="controlsOpen ? '{{ __('Hide Controls') }}' : '{{ __('Show Controls') }}'"
                         x-bind:aria-label="controlsOpen ? '{{ __('Hide Controls') }}' : '{{ __('Show Controls') }}'"/>
        </div>

        <div x-show="!isCompact || controlsOpen"
             x-cloak
             class="mt-2 flex min-w-0 flex-wrap items-center gap-2 min-[836px]:mt-0 min-[836px]:contents">
            {{-- Search --}}
            <div x-data="visionBoardSearch" class="flex min-w-0 flex-wrap items-center gap-2">
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
                        <x-icons.close class="size-3.5"/>
                    </button>
                </div>

                {{-- Tag filter --}}
                <div class="relative" x-data="{ tagFilterOpen: false }">
                    <button type="button"
                            x-on:click="tagFilterOpen = !tagFilterOpen"
                            :class="activeTagFilter ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                            class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                            title="{{ __('Filter by Tag') }}">
                        <x-icons.tag/>
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

            <span class="hidden h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600 min-[836px]:inline-block"></span>

            {{-- Canvas view toggles --}}
            <div x-data="visionBoardToggles" class="flex shrink-0 items-center gap-1">
                <button type="button" x-on:click="toggleGrid()"
                        :class="showGrid ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Show Grid') }}">
                    <x-icons.grid-bg/>
                </button>
                <button type="button" x-on:click="toggleGuides()"
                        :class="showGuides ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Show Guide Lines') }}">
                    <x-icons.guides/>
                </button>
                <button type="button" x-on:click="toggleSnap()"
                        :class="snapToGrid ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Snap to Grid') }}">
                    <x-icons.snap/>
                </button>

                <span class="mx-1 hidden h-5 w-px bg-zinc-300 dark:bg-zinc-600 min-[836px]:inline-block"></span>

                <flux:button size="sm" x-on:click="$dispatch('vb-center-canvas')" title="{{ __('Center Canvas') }}">
                    <x-icons.center-canvas/>
                </flux:button>
                <flux:button size="sm" x-on:click="$dispatch('vb-zoom-to-fit')" title="{{ __('Zoom to Fit') }}">
                    <x-icons.zoom-fit/>
                </flux:button>
            </div>

            <span class="hidden h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600 min-[836px]:inline-block"></span>

            {{-- Zoom --}}
            <div x-data="visionBoardZoom" class="flex shrink-0 items-center gap-2">
                <flux:button size="sm" icon="minus" x-on:click="zoomOut"/>
                <span class="w-12 text-center text-sm text-zinc-600 dark:text-zinc-400"
                      x-text="Math.round(zoom * 100) + '%'"></span>
                <flux:button size="sm" icon="plus" x-on:click="zoomIn"/>
            </div>
        </div>
    </div>

    {{-- Canvas --}}
    <div x-data="visionBoardViewport"
         x-on:scroll.passive="updateScroll()"
         class="flex-1 overflow-auto"
         style="cursor: grab;">

        <div wire:ignore
             x-data="{ get zoom() { return Alpine.store('visionBoard').zoom } }"
             :style="'transform: scale(' + zoom + '); transform-origin: 0 0; width: 4000px; height: 4000px;'"
             :class="Alpine.store('visionBoard').showGrid ? 'desktop-grid-bg' : ''"
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
                         mood: '{{ $card['mood'] ?? 'plain' }}'
                     })"
                     class="vb-card {{ $card['mood'] ? 'mood-' . $card['mood'] : 'mood-plain' }} touch-none select-none">
                    @if($card['title'])
                        <div class="vb-card-title">{{ $card['title'] }}</div>
                    @endif
                    @if(!empty($card['image_url']))
                        <img src="{{ $card['image_url'] }}" alt="{{ $card['preview'] }}" class="vb-card-img"
                             loading="lazy"/>
                    @else
                        <div
                            class="flex flex-1 items-center justify-center rounded-b-lg bg-zinc-200 text-zinc-400 dark:bg-zinc-700">
                            <x-icons.image class="size-10"/>
                        </div>
                    @endif
                    {{-- Relationship badge --}}
                    @if(($card['siblings_count'] ?? 0) > 0)
                        <div class="vb-card-links"
                             title="{{ trans_choice(':count link|:count links', $card['siblings_count']) }}">
                            <x-icons.link class="size-3"/>
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
                        <x-icons.edit/>
                        {{ __('Edit') }}
                    </button>
                    <button x-on:click="linkTo()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <x-icons.link/>
                        {{ __('Link to Entry/Note') }}
                    </button>

                    {{-- Mood submenu --}}
                    <div class="relative" x-data="{ moodOpen: false }">
                        <button x-on:click.stop="moodOpen = !moodOpen"
                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span class="flex items-center gap-2">
                                <x-icons.paint-brush/>
                                {{ __('Mood') }}
                            </span>
                            <x-icons.chevron-right class="size-3"/>
                        </button>
                        <div x-show="moodOpen" x-cloak
                             class="absolute left-full top-0 ml-1 min-w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach(Mood::cases() as $m)
                                @if($m !== Mood::Custom)
                                    <button x-on:click="changeMood('{{ $m->value }}')"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span
                                            class="mood-{{ $m->value }} inline-block size-3 rounded-full border"></span>
                                        {{ ucfirst($m->value) }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="relative" x-data="{ shareOpen: false }">
                        <button x-on:click.stop="shareOpen = !shareOpen; $wire.loadFriendsForSharing(); $wire.loadCurrentShares(entityId)"
                                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span class="flex items-center gap-2">
                                <x-icons.globe/>
                                {{ __('Share with') }}
                            </span>
                            <x-icons.chevron-right class="size-3"/>
                        </button>
                        <div x-show="shareOpen" x-cloak
                             class="absolute left-full top-0 ml-1 max-h-48 min-w-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            <template x-if="$wire.userFriends.length === 0">
                                <div class="px-3 py-1.5 text-sm text-zinc-400">
                                    {{ __('No friends to share with') }}
                                </div>
                            </template>
                            <template x-if="$wire.userFriends.length > 0">
                                <template x-for="friend in $wire.userFriends" :key="friend.id">
                                    <button type="button"
                                            x-on:click.stop="$wire.toggleShareWithFriend(entityId, friend.id)"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="inline-flex w-4 items-center justify-center">
                                            <svg x-show="$wire.currentEntitySharedFriends.includes(String(friend.id))" x-cloak class="size-3.5 text-zinc-600 dark:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </span>
                                        <span x-text="'@' + friend.username"></span>
                                    </button>
                                </template>
                            </template>
                        </div>
                    </div>

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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             x-on:keydown.escape.window="$wire.set('showEditorModal', false)">
            <div
                class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                x-on:click.away="$wire.set('showEditorModal', false)">
                <h2 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Edit Image') }}</h2>

                {{-- Title --}}
                <div class="mb-3">
                    <flux:input wire:model="editorTitle" :label="__('Title')"
                                placeholder="{{ __('Image title (shown as header)...') }}"/>
                </div>

                {{-- Description --}}
                <div class="mb-3">
                    <flux:input wire:model="editorAlt" :label="__('Description')"
                                placeholder="{{ __('Image description...') }}"/>
                </div>

                {{-- Mood selector --}}
                <div class="mb-3">
                    <label
                        class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Mood') }}</label>
                    <div class="flex flex-wrap gap-1">
                        @foreach(Mood::cases() as $m)
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
                        <flux:input type="color" wire:model="editorColorOverride" :label="__('Custom Color')"/>
                    </div>
                @endif

                {{-- Tags --}}
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tags') }}</label>
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
                    <flux:button size="sm" variant="primary" wire:click="saveEditor" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveEditor">{{ __('Save') }}</span>
                        <span wire:loading wire:target="saveEditor">…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Link Search Modal --}}
    @if($showLinkSearchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             x-on:keydown.escape.window="$wire.cancelLinkSearch()">
            <div
                class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
                x-on:click.away="$wire.cancelLinkSearch()">
                <h2 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Link to Diary Entry or Note') }}</h2>

                <flux:input wire:model.live.debounce.300ms="linkSearchQuery"
                            placeholder="{{ __('Search by title or content...') }}" autofocus/>

                @if(count($linkSearchResults) > 0)
                    <div class="mt-3 max-h-60 space-y-1 overflow-y-auto">
                        @foreach($linkSearchResults as $result)
                            <button type="button"
                                    wire:click="linkToEntity('{{ $result['id'] }}', '{{ $result['type'] }}')"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                <span
                                    class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-[0.625rem] font-semibold uppercase dark:bg-zinc-700">
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
        <x-icons.trash class="size-6"/>
        <span class="text-[0.6rem] font-medium uppercase tracking-wide">{{ __('Delete') }}</span>
    </div>

    {{-- Upload overlay --}}
    <div wire:loading.flex wire:target="imageUpload, uploadImage"
         class="fixed inset-0 z-99999 items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
            <x-icons.spinner class="size-10 text-white"/>
            <span class="text-sm font-medium text-white">{{ __('Uploading image...') }}</span>
        </div>
    </div>

    {{-- Guest Upload Warning Modal --}}
    @if($showGuestUploadWarning)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             x-on:keydown.escape.window="$wire.showGuestUploadWarning = false">
            <div
                class="w-full max-w-md rounded-xl border border-amber-200 bg-white p-6 shadow-2xl dark:border-amber-800 dark:bg-zinc-900"
                x-on:click.away="$wire.showGuestUploadWarning = false">
                <h2 class="mb-3 text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Guest Mode') }}</h2>
                <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Guest uploads are not allowed. You can browse demo images instead.') }}
                </p>
                <div class="flex justify-end">
                    <flux:button size="sm" wire:click="$set('showGuestUploadWarning', false)">{{ __('Got it') }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
