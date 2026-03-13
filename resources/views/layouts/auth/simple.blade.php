<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        {{-- Constellation SVG background --}}
        <div class="pointer-events-none fixed inset-0 overflow-hidden opacity-[0.03] dark:opacity-[0.06]">
            <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="constellation-dots" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                        <circle cx="15" cy="15" r="1" fill="currentColor" />
                        <circle cx="45" cy="45" r="1.5" fill="currentColor" />
                        <circle cx="30" cy="5" r="0.8" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#constellation-dots)" />
                <line x1="15" y1="15" x2="45" y2="45" stroke="currentColor" stroke-width="0.3" opacity="0.5" />
                <line x1="45" y1="45" x2="30" y2="5" stroke="currentColor" stroke-width="0.3" opacity="0.5" />
            </svg>
        </div>

        <div class="bg-background relative flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                {{-- Glassmorphism card --}}
                <div class="flex flex-col gap-6 rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl backdrop-blur-lg dark:border-zinc-700/40 dark:bg-zinc-900/70">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
