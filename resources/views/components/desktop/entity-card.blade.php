@props(['card'])

@php
    $createdAt = $card['created_at'] ? \Carbon\Carbon::parse($card['created_at']) : null;
    $updatedAt = $card['updated_at'] ? \Carbon\Carbon::parse($card['updated_at']) : null;
    $wasEdited = $createdAt && $updatedAt && !$createdAt->eq($updatedAt);
    $shortDate = $updatedAt?->format('H:i d/m/y');
    $tooltip = $wasEdited
        ? __('Created') . ': ' . $createdAt->translatedFormat('l, j F Y H:i:s') . "\n" . __('Last updated') . ': ' . $updatedAt->translatedFormat('l, j F Y H:i:s')
        : ($createdAt?->translatedFormat('l, j F Y H:i:s') ?? '');
@endphp

<div class="desktop-card-inner">
    @if($card['type'] === 'diary_entry')
        <div class="desktop-card-header">
            <span class="desktop-card-badge">{{ __('Diary') }}</span>
            @if($shortDate)
                <span class="desktop-card-date" title="{{ $tooltip }}">{{ $shortDate }}{{ $wasEdited ? '*' : '' }}</span>
            @endif
        </div>
        @if($card['title'])
            <h3 class="desktop-card-title">{{ $card['title'] }}</h3>
        @endif
        @if($card['preview'])
            <p class="desktop-card-preview">{{ $card['preview'] }}</p>
        @endif

    @elseif($card['type'] === 'note')
        <div class="desktop-card-header">
            <span class="desktop-card-badge">{{ __('Note') }}</span>
            @if($shortDate)
                <span class="desktop-card-date" title="{{ $tooltip }}">{{ $shortDate }}{{ $wasEdited ? '*' : '' }}</span>
            @endif
        </div>
        @if($card['title'])
            <h3 class="desktop-card-title">{{ $card['title'] }}</h3>
        @endif
        @if($card['preview'])
            <p class="desktop-card-preview">{{ $card['preview'] }}</p>
        @endif

    @elseif($card['type'] === 'postit')
        @if($shortDate)
            <div class="desktop-card-header">
                <span class="desktop-card-date" title="{{ $tooltip }}">{{ $shortDate }}{{ $wasEdited ? '*' : '' }}</span>
            </div>
        @endif
        <p class="desktop-card-preview postit-editable-text"
           contenteditable="true"
           x-on:blur="$wire.quickSavePostit('{{ $card['id'] }}', $event.target.innerText)"
           x-on:keydown.enter.prevent="$event.target.blur()"
           x-on:mousedown.stop
           x-on:dblclick.stop>{{ $card['preview'] ?: __('Empty post-it') }}</p>

    @elseif($card['type'] === 'image')
        <div class="desktop-card-header">
            <span class="desktop-card-badge">{{ __('Image') }}</span>
            @if($shortDate)
                <span class="desktop-card-date" title="{{ $tooltip }}">{{ $shortDate }}{{ $wasEdited ? '*' : '' }}</span>
            @endif
        </div>
        @if(!empty($card['image_url']))
            <img src="{{ $card['image_url'] }}" alt="{{ $card['preview'] ?: __('Image') }}" class="mt-1 max-h-40 w-full rounded object-cover" loading="lazy" />
        @else
            <p class="desktop-card-preview">{{ $card['preview'] ?: __('No description') }}</p>
        @endif
    @endif

    {{-- Relationship indicators --}}
    @php
        $childrenCount = $card['children_count'] ?? 0;
        $siblingsCount = $card['siblings_count'] ?? 0;
        $parentId = $card['parent_id'] ?? null;
    @endphp

    @if($childrenCount > 0 || $siblingsCount > 0 || $parentId)
        <div class="desktop-card-relations">
            @if($parentId)
                <span class="desktop-card-relation-badge desktop-card-relation-attached" title="{{ __('Attached to parent') }}">
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                </span>
            @endif
            @if($childrenCount > 0)
                <span class="desktop-card-relation-badge desktop-card-relation-children" title="{{ trans_choice(':count child|:count children', $childrenCount) }}">
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-6A2.25 2.25 0 019.75 18v-2.25m6.75-7.5l-3 3m0 0l-3-3m3 3v-6" /></svg>
                    {{ $childrenCount }}
                </span>
            @endif
            @if($siblingsCount > 0)
                <span class="desktop-card-relation-badge desktop-card-relation-siblings" title="{{ trans_choice(':count sibling|:count siblings', $siblingsCount) }}">
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.69" /></svg>
                    {{ $siblingsCount }}
                </span>
            @endif
        </div>
    @endif

    @if($card['is_public'])
        <span class="desktop-card-public" title="{{ __('Public') }}">
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9 9 0 0 1 3 12c0-1.47.353-2.856.978-4.082" /></svg>
        </span>
    @endif
</div>
