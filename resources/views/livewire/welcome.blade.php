<div class="flex flex-col items-center gap-8 py-8 text-center">
    {{-- Logo --}}
    <div>
        <x-app-logo-icon class="size-14 fill-current text-zinc-400 dark:text-zinc-500" />
    </div>

    {{-- Welcome text --}}
    <div class="space-y-3">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">
            {{ __('Welcome, :name!', ['name' => $name]) }}
        </h1>
        <p class="max-w-xs text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Your personal constellation is ready. A space for your thoughts, memories, and dreams — all connected like stars in the sky.') }}
        </p>
    </div>

    {{-- Features preview --}}
    <div class="grid w-full max-w-xs gap-3 text-left">
        <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
            <flux:icon name="squares-2x2" class="size-5 text-zinc-400" />
            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Canvas — your creative workspace') }}</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
            <flux:icon name="book-open" class="size-5 text-zinc-400" />
            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Diary — capture your days') }}</span>
        </div>
        <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
            <flux:icon name="eye" class="size-5 text-zinc-400" />
            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Vision Board — visualize your goals') }}</span>
        </div>
    </div>

    {{-- CTA --}}
    <flux:button variant="primary" wire:click="start" class="w-full max-w-xs">
        {{ __("Let's go ✨") }}
    </flux:button>
</div>
