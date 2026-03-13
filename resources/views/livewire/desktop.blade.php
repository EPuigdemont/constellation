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
    <flux:modal wire:model="showEditorModal" class="w-full max-w-lg">
        <div class="space-y-4">
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

            <flux:field>
                <flux:label>{{ __('Body') }}</flux:label>
                <flux:textarea wire:model="editorBody" rows="6" placeholder="{{ __('Write something...') }}" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Mood') }}</flux:label>
                <flux:select wire:model="editorMood">
                    @foreach(\App\Enums\Mood::cases() as $mood)
                        <flux:select.option value="{{ $mood->value }}">{{ ucfirst($mood->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button x-on:click="$wire.showEditorModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveEditor">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
