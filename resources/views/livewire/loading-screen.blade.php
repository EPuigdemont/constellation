<div class="flex flex-col items-center justify-center gap-6 py-12"
     x-data="{
        init() {
            setTimeout(() => {
                window.location.href = '{{ route('canvas') }}';
            }, 2500);
        }
     }">

    {{-- Pulsing logo --}}
    <div class="animate-pulse">
        <x-app-logo-icon class="size-16 fill-current text-zinc-400 dark:text-zinc-500" />
    </div>

    {{-- Loading message --}}
    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ $message }}
    </p>

    {{-- Progress dots --}}
    <div class="flex gap-1.5">
        <span class="h-1.5 w-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600" style="animation: loading-dot 1.4s ease-in-out infinite;"></span>
        <span class="h-1.5 w-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600" style="animation: loading-dot 1.4s ease-in-out 0.2s infinite;"></span>
        <span class="h-1.5 w-1.5 rounded-full bg-zinc-300 dark:bg-zinc-600" style="animation: loading-dot 1.4s ease-in-out 0.4s infinite;"></span>
    </div>

    <style>
        @keyframes loading-dot {
            0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1.2); }
        }
    </style>
</div>
