{{-- Diary Tag Editor partial --}}
{{-- @param array $tagIds - current selected tag IDs --}}
{{-- @param string $searchProp - wire:model property for search --}}
{{-- @param string $toggleMethod - Livewire method to toggle a tag --}}
{{-- @param string $createMethod - Livewire method to create inline tag --}}

<div x-data="{ tagDropOpen: false }" class="relative">
    <div class="mb-1 flex flex-wrap gap-1">
        @foreach($availableTags as $tag)
            @if(in_array($tag['id'], $tagIds, true))
                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2 py-0.5 text-xs dark:bg-zinc-700">
                    {{ $tag['name'] }}
                    <button type="button" wire:click="{{ $toggleMethod }}('{{ $tag['id'] }}')" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">&times;</button>
                </span>
            @endif
        @endforeach
    </div>
    <input type="text"
           wire:model.live.debounce.300ms="{{ $searchProp }}"
           x-on:focus="tagDropOpen = true"
           x-on:click.away="tagDropOpen = false"
           placeholder="{{ __('Search or create tag...') }}"
           class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div x-show="tagDropOpen" x-cloak class="absolute z-50 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
        @php
            $searchVal = $this->{$searchProp} ?? '';
            $filteredTags = collect($availableTags)->filter(fn ($tag) => $searchVal === '' || str_contains(strtolower($tag['name']), strtolower($searchVal)));
        @endphp
        @forelse($filteredTags as $tag)
            <button type="button" wire:click="{{ $toggleMethod }}('{{ $tag['id'] }}')" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                @if(in_array($tag['id'], $tagIds, true))
                    <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                @else
                    <span class="size-4"></span>
                @endif
                {{ $tag['name'] }}
            </button>
        @empty
            @if($searchVal !== '')
                <button type="button" wire:click="{{ $createMethod }}('{{ addslashes($searchVal) }}')" x-on:click="tagDropOpen = false" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:text-blue-400 dark:hover:bg-zinc-800">
                    + {{ __('Create') }} "{{ $searchVal }}"
                </button>
            @endif
        @endforelse
        @if($searchVal !== '' && $filteredTags->isNotEmpty() && !$filteredTags->contains(fn ($t) => strtolower($t['name']) === strtolower($searchVal)))
            <button type="button" wire:click="{{ $createMethod }}('{{ addslashes($searchVal) }}')" x-on:click="tagDropOpen = false" class="flex w-full items-center gap-2 border-t border-zinc-200 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-800">
                + {{ __('Create') }} "{{ $searchVal }}"
            </button>
        @endif
    </div>
</div>
