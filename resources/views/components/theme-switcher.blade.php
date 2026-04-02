@php
    $themes = \App\Enums\Theme::cases();
    $currentTheme = auth()->user()?->theme ?? 'summer';
    $currentThemeEnum = \App\Enums\Theme::tryFrom($currentTheme) ?? \App\Enums\Theme::Summer;
@endphp

<div x-data="{
        current: '{{ $currentTheme }}',
        async setTheme(value) {
            this.current = value;
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

    {{-- Collapsed view: single dot with context menu --}}
    <flux:dropdown position="right" align="start" class="not-in-data-flux-sidebar-collapsed-desktop:hidden flex justify-center py-1.5">
        <button type="button"
                class="flex h-5 w-5 items-center justify-center rounded-full transition-all hover:scale-110"
                :style="'background-color: ' + ({
                    spring: '{{ \App\Enums\Theme::Spring->swatchColor() }}',
                    summer: '{{ \App\Enums\Theme::Summer->swatchColor() }}',
                    autumn: '{{ \App\Enums\Theme::Autumn->swatchColor() }}',
                    winter: '{{ \App\Enums\Theme::Winter->swatchColor() }}',
                    love: '{{ \App\Enums\Theme::Love->swatchColor() }}',
                    breeze: '{{ \App\Enums\Theme::Breeze->swatchColor() }}',
                    night: '{{ \App\Enums\Theme::Night->swatchColor() }}',
                    cozy: '{{ \App\Enums\Theme::Cozy->swatchColor() }}'
                }[current] || '{{ $currentThemeEnum->swatchColor() }}')"
                title="{{ __('Change theme') }}">
        </button>

        <flux:menu class="min-w-40">
            @foreach($themes as $theme)
                <flux:menu.item as="button"
                                type="button"
                                x-on:click="setTheme('{{ $theme->value }}')"
                                class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full"
                          style="background-color: {{ $theme->swatchColor() }};"></span>
                    <span>{{ $theme->label() }}</span>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
