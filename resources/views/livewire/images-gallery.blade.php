<div class="page-glitter-wrapper flex h-screen flex-col overflow-hidden">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->theme ?? 'summer' }}"></canvas>
    {{-- Toolbar --}}
    <div class="relative z-10 flex items-center gap-3 border-b border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-header-bg,theme(colors.zinc.50))] px-2 py-1.5 dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-header-bg,theme(colors.zinc.900))]">
        <flux:heading size="lg">{{ __('Images') }}</flux:heading>

        <flux:spacer />

        <span class="text-sm text-zinc-400">{{ trans_choice(':count image|:count images', $images->count()) }}</span>
    </div>

    {{-- Gallery grid --}}
    <div class="flex-1 overflow-y-auto">
        @if($images->isEmpty())
            <div class="flex items-center justify-center py-20 text-zinc-400">
                {{ __('No images yet. Upload one from the Desktop.') }}
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 p-0 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($images as $image)
                    <div class="group cursor-pointer overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
                         x-on:dblclick="$wire.openImageModal('{{ $image['id'] }}', '{{ $image['url'] }}', '{{ addslashes($image['alt']) }}')">
                        <div class="aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            <img src="{{ $image['url'] }}"
                                 alt="{{ $image['alt'] }}"
                                 class="size-full object-cover transition-transform group-hover:scale-105"
                                 loading="lazy" />
                        </div>
                        <div class="p-2">
                            <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300" title="{{ $image['alt'] }}">
                                {{ $image['alt'] ?: __('Untitled') }}
                            </p>
                            <p class="text-[0.65rem] text-zinc-400">{{ $image['created_at'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Image Action Modal --}}
    @if($showImageModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeImageModal()">
            <div class="absolute inset-0 bg-black/60" wire:click="closeImageModal"></div>
            <div class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col rounded-xl border border-[var(--theme-border)] shadow-2xl"
                 style="background: var(--theme-bg);">
                {{-- Image preview --}}
                <div class="flex-1 overflow-hidden rounded-t-xl bg-[var(--theme-bg-secondary)]">
                    <img src="{{ $modalImageUrl }}" alt="{{ $modalImageAlt }}" class="mx-auto max-h-[60vh] object-contain" />
                </div>

                {{-- Info + Actions --}}
                <div class="flex items-center justify-between border-t border-[var(--theme-border)] px-5 py-3">
                    <div class="text-sm font-medium text-[var(--theme-text)]">
                        {{ $modalImageAlt ?: __('Untitled') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ $modalImageUrl }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-md border border-[var(--theme-border)] px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                            <flux:icon name="eye" variant="outline" class="size-4" />
                            {{ __('View') }}
                        </a>
                        <a href="{{ $modalImageUrl }}" download
                           class="inline-flex items-center gap-1.5 rounded-md border border-[var(--theme-border)] px-3 py-1.5 text-sm text-[var(--theme-text)] hover:bg-[var(--theme-accent)]/10">
                            <flux:icon name="arrow-down-tray" variant="outline" class="size-4" />
                            {{ __('Download') }}
                        </a>
                        <flux:button size="sm" variant="danger" wire:click="deleteImage('{{ $modalImageId }}')" wire:confirm="{{ __('Are you sure you want to delete this image?') }}">
                            <flux:icon name="trash" variant="outline" class="size-4" />
                            {{ __('Delete') }}
                        </flux:button>
                        <flux:button size="sm" variant="subtle" wire:click="closeImageModal" icon="x-mark" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
