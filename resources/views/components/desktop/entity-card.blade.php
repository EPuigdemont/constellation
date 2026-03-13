@props(['card'])

@php
    $createdAt = $card['created_at'] ? \Carbon\Carbon::parse($card['created_at']) : null;
    $updatedAt = $card['updated_at'] ? \Carbon\Carbon::parse($card['updated_at']) : null;
    $wasEdited = $createdAt && $updatedAt && !$createdAt->eq($updatedAt);
    $shortDate = $updatedAt?->format('H:i d/m/y');
    $tooltip = $wasEdited
        ? __('Created') . ': ' . $createdAt->format('l, j F Y \a\t H:i:s') . "\n" . __('Last updated') . ': ' . $updatedAt->format('l, j F Y \a\t H:i:s')
        : ($createdAt?->format('l, j F Y \a\t H:i:s') ?? '');
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
        <p class="desktop-card-preview">{{ $card['preview'] ?: __('Empty post-it') }}</p>

    @elseif($card['type'] === 'image')
        <div class="desktop-card-header">
            <span class="desktop-card-badge">{{ __('Image') }}</span>
            @if($shortDate)
                <span class="desktop-card-date" title="{{ $tooltip }}">{{ $shortDate }}{{ $wasEdited ? '*' : '' }}</span>
            @endif
        </div>
        <p class="desktop-card-preview">{{ $card['preview'] ?: __('No description') }}</p>
    @endif

    @if($card['is_public'])
        <span class="desktop-card-public" title="{{ __('Public') }}">
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9 9 0 0 1 3 12c0-1.47.353-2.856.978-4.082" /></svg>
        </span>
    @endif
</div>
