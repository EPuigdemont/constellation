<div wire:poll.60s>
    <a href="{{ route('notifications') }}" wire:navigate class="relative">
        <flux:icon name="bell-alert" variant="{{ $count > 0 ? 'solid' : 'outline' }}" class="size-5 {{ $count > 0 ? 'text-[var(--theme-accent)]' : 'text-[var(--theme-text-muted)]' }}" />
        @if ($count > 0)
            <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--theme-accent)] px-1 text-[0.6rem] font-bold text-white">
                {{ $count > 99 ? '99+' : $count }}
            </span>
        @endif
    </a>
</div>
