<div class="flex h-screen flex-col overflow-hidden">
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Images') }}</flux:heading>

        <flux:spacer />

        <span class="text-sm text-zinc-400">{{ trans_choice(':count image|:count images', $images->count()) }}</span>
    </div>

    {{-- Gallery grid --}}
    <div class="flex-1 overflow-y-auto bg-zinc-100 dark:bg-zinc-800">
        @if($images->isEmpty())
            <div class="flex items-center justify-center py-20 text-zinc-400">
                {{ __('No images yet. Upload one from the Desktop.') }}
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($images as $image)
                    <div class="group overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
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
</div>
