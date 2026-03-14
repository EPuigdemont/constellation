<div class="constellation-wrapper relative flex h-[calc(100vh-2rem)] flex-col overflow-hidden"
     x-data="constellationApp()"
     x-init="init($wire)"
>
    {{-- Starry background --}}
    <div class="constellation-stars pointer-events-none absolute inset-0"
         x-ref="starsCanvas"
         x-on:mousemove.window="parallax($event)"></div>

    {{-- Top filter bar --}}
    <div class="relative z-10 flex flex-wrap items-center gap-2 px-4 py-3"
         style="background: color-mix(in srgb, var(--theme-bg) 60%, transparent); backdrop-filter: blur(12px);">
        <h1 class="mr-2 text-lg font-semibold text-[var(--theme-text)]">
            {{ __('Constellation') }}
        </h1>

        <flux:select wire:model.live="filterType" size="sm" class="w-28">
            <option value="all">{{ __('All') }}</option>
            <option value="diary">{{ __('Diary') }}</option>
            <option value="note">{{ __('Notes') }}</option>
            <option value="postit">{{ __('Post-its') }}</option>
            <option value="image">{{ __('Images') }}</option>
        </flux:select>

        <flux:select wire:model.live="filterTag" size="sm" class="w-32">
            <option value="">{{ __('All tags') }}</option>
            @foreach ($userTags as $tag)
                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="filterMonth" size="sm" class="w-28">
            <option value="">{{ __('Month') }}</option>
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
            @endfor
        </flux:select>

        <flux:select wire:model.live="filterWeekday" size="sm" class="w-28">
            <option value="">{{ __('Weekday') }}</option>
            @foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $i => $day)
                <option value="{{ $i + 1 }}">{{ __($day) }}</option>
            @endforeach
        </flux:select>

        <input type="date" wire:model.live="filterDateFrom" class="rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-2 py-1 text-xs text-[var(--theme-text)]" />
        <span class="text-xs text-[var(--theme-text-muted)]">{{ __('to') }}</span>
        <input type="date" wire:model.live="filterDateTo" class="rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-2 py-1 text-xs text-[var(--theme-text)]" />
    </div>

    {{-- D3 SVG container --}}
    <div class="relative z-0 flex-1" x-ref="graphContainer">
        <svg x-ref="graphSvg" class="h-full w-full"></svg>
    </div>

    {{-- Entity preview panel --}}
    <div x-show="selectedNode" x-cloak x-transition
         class="absolute bottom-4 right-4 z-20 w-80 rounded-xl border border-[var(--theme-border)] p-4 shadow-xl"
         style="background: color-mix(in srgb, var(--theme-bg) 92%, transparent); backdrop-filter: blur(16px);">
        <template x-if="selectedNode">
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full"
                              :class="'mood-dot-' + selectedNode.mood"
                              :style="selectedNode.color_override ? 'background:' + selectedNode.color_override : ''"></span>
                        <span class="text-sm font-semibold text-[var(--theme-text)]" x-text="selectedNode.title"></span>
                    </div>
                    <button x-on:click="selectedNode = null" class="text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mb-2 flex items-center gap-2 text-xs text-[var(--theme-text-muted)]">
                    <span class="rounded-full px-2 py-0.5 capitalize"
                          :class="'mood-' + selectedNode.mood + '-badge'"
                          x-text="selectedNode.type"></span>
                    <span x-text="new Date(selectedNode.created_at).toLocaleDateString()"></span>
                </div>
                <p class="mb-2 text-xs leading-relaxed text-[var(--theme-text-muted)]" x-text="selectedNode.preview"></p>
                <template x-if="selectedNode.tags && selectedNode.tags.length">
                    <div class="flex flex-wrap gap-1">
                        <template x-for="tag in selectedNode.tags" :key="tag">
                            <span class="rounded-full bg-[var(--theme-accent)]/10 px-2 py-0.5 text-[0.65rem] text-[var(--theme-accent)]" x-text="tag"></span>
                        </template>
                    </div>
                </template>
                <div class="mt-2 text-[0.6rem] text-[var(--theme-text-muted)]">
                    <span x-text="connectedCount + ' connection' + (connectedCount !== 1 ? 's' : '')"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Legend --}}
    <div class="absolute bottom-4 left-4 z-20 rounded-lg border border-[var(--theme-border)] px-3 py-2 text-xs"
         style="background: color-mix(in srgb, var(--theme-bg) 85%, transparent); backdrop-filter: blur(12px);">
        <div class="mb-1 font-medium text-[var(--theme-text)]">{{ __('Edges') }}</div>
        <div class="flex flex-col gap-0.5 text-[var(--theme-text-muted)]">
            <div class="flex items-center gap-2"><span class="inline-block h-0.5 w-4 bg-[var(--theme-accent)]"></span> {{ __('Parent/Child') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block h-0.5 w-4 border-t-2 border-dashed border-[var(--theme-accent)]"></span> {{ __('Sibling') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block h-0.5 w-4 bg-[var(--theme-accent)] opacity-40"></span> {{ __('Shared tag') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block h-0.5 w-4 bg-[var(--theme-text-muted)] opacity-30"></span> {{ __('Same day') }}</div>
        </div>
        <div class="mt-2 mb-1 font-medium text-[var(--theme-text)]">{{ __('Nodes') }}</div>
        <div class="flex flex-col gap-0.5 text-[var(--theme-text-muted)]">
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full bg-[var(--theme-accent)]"></span> {{ __('Diary') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded bg-[var(--theme-accent)]"></span> {{ __('Note') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rotate-45 bg-[var(--theme-accent)]"></span> {{ __('Post-it') }}</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-sm border-2 border-[var(--theme-accent)]"></span> {{ __('Image') }}</div>
        </div>
    </div>

    {{-- Pass graph data to Alpine --}}
    <script type="application/json" x-ref="graphData">
        @json($graphData)
    </script>
</div>
