@php
    $themes = \App\Enums\Theme::cases();
    $theme = \App\Enums\Theme::tryFrom(auth()->user()?->theme ?? 'summer') ?? \App\Enums\Theme::Summer;
@endphp

<div x-data="{
        dropdownOpen: false,
        async setTheme(value) {
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
     }"
     class="relative flex items-center">
    {{-- Expanded: just show the icon --}}
    <span class="in-data-flux-sidebar-collapsed-desktop:hidden flex items-center" title="{{ $theme->label() }} theme">
        <flux:icon :name="$theme->icon()" variant="outline" class="size-4" style="color: {{ $theme->swatchColor() }};" />
    </span>

    {{-- Collapsed: icon is a button that opens a dropdown --}}
    <div class="not-in-data-flux-sidebar-collapsed-desktop:hidden relative">
        <button type="button"
                x-on:click="dropdownOpen = !dropdownOpen"
                class="flex items-center"
                title="{{ __('Change theme') }}">
            <flux:icon :name="$theme->icon()" variant="outline" class="size-4" style="color: {{ $theme->swatchColor() }};" />
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
             class="absolute left-full top-0 z-50 ml-2 rounded-lg border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex flex-col gap-1.5">
                @foreach($themes as $t)
                    <button type="button"
                            x-on:click="setTheme('{{ $t->value }}')"
                            class="flex items-center gap-2 whitespace-nowrap rounded px-2 py-1 text-xs hover:bg-zinc-100 dark:hover:bg-zinc-700"
                            title="{{ $t->label() }}">
                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $t->swatchColor() }};"></span>
                        {{ $t->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
