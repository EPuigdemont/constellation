<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:button size="sm" icon="plus" x-on:click="$dispatch('create-entity', { mode: 'diary' })">
            {{ __('Diary Entry') }}
        </flux:button>
        <flux:button size="sm" icon="plus" x-on:click="$dispatch('create-entity', { mode: 'note' })">
            {{ __('Note') }}
        </flux:button>
        <flux:button size="sm" icon="plus" x-on:click="$dispatch('create-entity', { mode: 'postit' })">
            {{ __('Post-it') }}
        </flux:button>

        <flux:spacer />

        <div x-data="desktopZoom" class="flex items-center gap-2">
            <flux:button size="sm" icon="minus" x-on:click="zoomOut" />
            <span class="w-12 text-center text-sm text-zinc-600 dark:text-zinc-400"
                  x-text="Math.round(zoom * 100) + '%'"></span>
            <flux:button size="sm" icon="plus" x-on:click="zoomIn" />
        </div>
    </div>

    {{-- Canvas container --}}
    <div class="relative flex-1 overflow-auto bg-zinc-100 dark:bg-zinc-800"
         id="desktop-viewport"
         x-data="desktopViewport"
         x-on:scroll="updateScroll()">
        <div wire:ignore
             x-data="{ get zoom() { return Alpine.store('desktop').zoom } }"
             :style="'transform: scale(' + zoom + '); transform-origin: 0 0; width: 4000px; height: 4000px;'"
             class="relative"
             id="desktop-canvas"
             x-on:contextmenu.prevent="$dispatch('desktop-context', { x: $event.clientX, y: $event.clientY })">

            @foreach($cards as $index => $card)
                <div wire:key="card-{{ $card['id'] }}"
                     data-card-id="{{ $card['id'] }}"
                     x-data="desktopCard({{ Js::from(array_merge($card, ['is_owner' => $card['owner_id'] === auth()->id()])) }})"
                     x-init="initDrag()"
                     :style="'position: absolute; left: ' + cardX + 'px; top: ' + cardY + 'px; z-index: ' + cardZ + ';'"
                     x-on:contextmenu.prevent.stop="$dispatch('desktop-context', {
                         x: $event.clientX,
                         y: $event.clientY,
                         entityId: '{{ $card['id'] }}',
                         entityType: '{{ $card['type'] }}',
                         isOwner: {{ $card['owner_id'] === auth()->id() ? 'true' : 'false' }},
                         isPublic: {{ $card['is_public'] ? 'true' : 'false' }},
                         mood: '{{ $card['mood'] ?? 'plain' }}'
                     })"
                     class="desktop-card {{ $card['mood'] ? 'mood-' . $card['mood'] : 'mood-plain' }} card-type-{{ $card['type'] }} touch-none select-none">
                    <x-desktop.entity-card :card="$card" />
                </div>
            @endforeach
        </div>
    </div>

    {{-- Context Menu --}}
    <div x-data="desktopContextMenu"
         x-show="open"
         x-on:desktop-context.window="openMenu($event.detail)"
         x-on:click.window="close()"
         x-on:keydown.escape.window="close()"
         x-cloak
         :style="'position: fixed; left: ' + menuX + 'px; top: ' + menuY + 'px; z-index: 9999;'"
         class="min-w-48 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">

        <template x-if="entityId">
            <div>
                <template x-if="isOwner">
                    <div>
                        <button x-on:click="edit()" class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            {{ __('Edit') }}
                        </button>

                        <button x-on:click="togglePublic()" class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span x-text="isPublic ? '{{ __('Make Private') }}' : '{{ __('Make Public') }}'"></span>
                        </button>

                        {{-- Mood submenu --}}
                        <div class="relative" x-data="{ moodOpen: false }">
                            <button x-on:click.stop="moodOpen = !moodOpen"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                {{ __('Change Mood') }}
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                            <div x-show="moodOpen" x-cloak
                                 class="absolute left-full top-0 ml-1 min-w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach(\App\Enums\Mood::cases() as $mood)
                                    <button x-on:click="changeMood('{{ $mood->value }}')"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="inline-block size-3 rounded-full mood-dot-{{ $mood->value }}"></span>
                                        {{ ucfirst($mood->value) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

                        <button x-on:click="deleteEntity()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </template>

                <template x-if="!isOwner">
                    <div class="px-3 py-1.5 text-sm text-zinc-400">
                        {{ __('Shared entity (read-only)') }}
                    </div>
                </template>
            </div>
        </template>

        <template x-if="!entityId">
            <div class="px-3 py-1.5 text-sm text-zinc-400">
                {{ __('Right-click a card for options') }}
            </div>
        </template>
    </div>

    {{-- Editor Modal --}}
    <flux:modal wire:model="showEditorModal" class="w-full max-w-3xl">
        <div class="space-y-4" x-data="tiptapEditor" x-on:keydown.escape.window="syncToWire()">
            <flux:heading size="lg">
                <span x-text="$wire.editingEntityId ? '{{ __('Edit') }}' : '{{ __('New') }}'"></span>
                <span x-text="$wire.editorMode === 'diary' ? '{{ __('Diary Entry') }}' : '{{ __('Note') }}'"></span>
            </flux:heading>

            @if($editorMode === 'diary' || $editorMode === 'note')
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="editorTitle" placeholder="{{ __('Enter title...') }}" />
                </flux:field>
            @endif

            {{-- Tiptap Toolbar --}}
            <div class="flex flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-[var(--card-border,var(--color-zinc-200))] bg-[var(--card-bg,var(--color-zinc-50))] px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900">
                <button type="button" x-on:click="toggleBold()" :class="isActive('bold') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Bold') }}">B</button>
                <button type="button" x-on:click="toggleItalic()" :class="isActive('italic') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm italic hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Italic') }}">I</button>
                <button type="button" x-on:click="toggleUnderline()" :class="isActive('underline') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm underline hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Underline') }}">U</button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="setHeading(1)" :class="isActive('heading', {level: 1}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Heading 1') }}">H1</button>
                <button type="button" x-on:click="setHeading(2)" :class="isActive('heading', {level: 2}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Heading 2') }}">H2</button>
                <button type="button" x-on:click="setHeading(3)" :class="isActive('heading', {level: 3}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Heading 3') }}">H3</button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="toggleBulletList()" :class="isActive('bulletList') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Bullet List') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                </button>
                <button type="button" x-on:click="toggleOrderedList()" :class="isActive('orderedList') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Ordered List') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003h12m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H4.372m-3.279 7.561c.282-.533.66-.89 1.089-1.075.293-.126.628-.193.961-.186.593.012 1.126.277 1.37.76.257.508.035 1.119-.488 1.597l-2.066 1.884h2.736M2.98 17.243h.045" /></svg>
                </button>
                <button type="button" x-on:click="toggleBlockquote()" :class="isActive('blockquote') ? 'bg-zinc-200 dark:bg-zinc-700' : ''" class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Blockquote') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                </button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="insertImage()" class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700" title="{{ __('Insert Image') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Zm16.5-13.5h.008v.008h-.008V7.5Zm0 0a.375.375 0 1 0-.75 0 .375.375 0 0 0 .75 0Z" /></svg>
                </button>
                <input type="file" x-ref="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" x-on:change="handleImageSelect($event)" />
            </div>

            {{-- Tiptap Editor Content --}}
            <div wire:ignore class="tiptap-editor-content min-h-48 rounded-b-lg border border-[var(--card-border,var(--color-zinc-200))] p-3 dark:border-zinc-700">
                <div x-ref="editorElement"></div>
            </div>

            {{-- Metadata Row --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                {{-- Mood --}}
                <flux:field>
                    <flux:label>{{ __('Mood') }}</flux:label>
                    <flux:select wire:model="editorMood">
                        @foreach(\App\Enums\Mood::cases() as $mood)
                            <flux:select.option value="{{ $mood->value }}">{{ ucfirst($mood->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Color Override --}}
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

                {{-- Tags --}}
                <flux:field>
                    <flux:label>{{ __('Tags') }}</flux:label>
                    <div x-data="{ tagDropdownOpen: false }" class="relative">
                        {{-- Selected tags as pills --}}
                        <div class="mb-1 flex flex-wrap gap-1">
                            @foreach($availableTags as $tag)
                                @if(in_array($tag['id'], $editorTagIds, true))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2 py-0.5 text-xs dark:bg-zinc-700">
                                        {{ $tag['name'] }}
                                        <button type="button" wire:click="toggleTag('{{ $tag['id'] }}')" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        {{-- Search input --}}
                        <input type="text"
                               wire:model.live.debounce.300ms="tagSearch"
                               x-on:focus="tagDropdownOpen = true"
                               x-on:click.away="tagDropdownOpen = false"
                               placeholder="{{ __('Search or create tag...') }}"
                               class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">

                        {{-- Dropdown --}}
                        <div x-show="tagDropdownOpen" x-cloak class="absolute z-50 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @php
                                $filteredTags = collect($availableTags)->filter(function ($tag) {
                                    if ($this->tagSearch === '') return true;
                                    return str_contains(strtolower($tag['name']), strtolower($this->tagSearch));
                                });
                            @endphp

                            @forelse($filteredTags as $tag)
                                <button type="button"
                                        wire:click="toggleTag('{{ $tag['id'] }}')"
                                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    @if(in_array($tag['id'], $editorTagIds, true))
                                        <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
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

                            @if($tagSearch !== '' && $filteredTags->isNotEmpty())
                                @php
                                    $exactMatch = $filteredTags->contains(fn ($t) => strtolower($t['name']) === strtolower($tagSearch));
                                @endphp
                                @unless($exactMatch)
                                    <button type="button"
                                            wire:click="createTagInline('{{ addslashes($tagSearch) }}')"
                                            x-on:click="tagDropdownOpen = false"
                                            class="flex w-full items-center gap-2 border-t border-zinc-200 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-800">
                                        + {{ __('Create') }} "{{ $tagSearch }}"
                                    </button>
                                @endunless
                            @endif
                        </div>
                    </div>
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button x-on:click="syncToWire(); $wire.showEditorModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" x-on:click="syncToWire()" wire:click="saveEditor">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
