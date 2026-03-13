@php
    $themes = \App\Enums\Theme::cases();
    $currentTheme = auth()->user()?->theme ?? 'summer';
    $currentThemeEnum = \App\Enums\Theme::tryFrom($currentTheme) ?? \App\Enums\Theme::Summer;
@endphp

<div x-data="{
        current: '{{ $currentTheme }}',
        dropdownOpen: false,
        async setTheme(value) {
            this.current = value;
            this.dropdownOpen = false;
            await fetch('{{ route('theme.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                },
                body: JSON.stringify({ theme: value })
            });
            window.location.reload();
        }
     }">
    {{-- Expanded view: row of colored dots --}}
    <div class="in-data-flux-sidebar-collapsed-desktop:hidden flex items-center justify-center gap-1.5 px-2 py-1.5">
        @foreach($themes as $theme)
            <button type="button"
                    x-on:click="setTheme('{{ $theme->value }}')"
                    :class="current === '{{ $theme->value }}' ? 'ring-2 ring-offset-1 ring-zinc-400 dark:ring-zinc-500 dark:ring-offset-zinc-900 scale-110' : 'opacity-60 hover:opacity-100'"
                    class="h-3 w-3 rounded-full transition-all"
                    style="background-color: {{ $theme->swatchColor() }};"
                    title="{{ $theme->label() }}">
            </button>
        @endforeach
    </div>

    {{-- Collapsed view: single dot with dropdown --}}
    <div class="not-in-data-flux-sidebar-collapsed-desktop:hidden relative flex justify-center py-1.5">
        <button type="button"
                x-on:click="dropdownOpen = !dropdownOpen"
                class="flex h-5 w-5 items-center justify-center rounded-full transition-all hover:scale-110"
                style="background-color: {{ $currentThemeEnum->swatchColor() }};"
                title="{{ __('Change theme') }}">
        </button>

        <div x-show="dropdownOpen"
             x-cloak
             x-on:click.outside="dropdownOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute left-full bottom-0 z-50 ml-2 rounded-lg border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-col gap-1.5">
                @foreach($themes as $theme)
                    <button type="button"
                            x-on:click="setTheme('{{ $theme->value }}')"
                            :class="current === '{{ $theme->value }}' ? 'ring-2 ring-zinc-400 dark:ring-zinc-500' : 'hover:scale-110'"
                            class="h-4 w-4 rounded-full transition-all"
                            style="background-color: {{ $theme->swatchColor() }};"
                            title="{{ $theme->label() }}">
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
