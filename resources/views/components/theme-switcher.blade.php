@php
    $themes = \App\Enums\Theme::cases();
    $currentTheme = auth()->user()?->theme ?? 'summer';
@endphp

<div x-data="{
        current: '{{ $currentTheme }}',
        async setTheme(value) {
            this.current = value;
            document.body.className = document.body.className.replace(/theme-\w+/, 'theme-' + value);
            document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: value } }));
            await fetch('{{ route('theme.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}'
                },
                body: JSON.stringify({ theme: value })
            });
        }
     }"
     class="flex items-center justify-center gap-1.5 px-2 py-1.5">
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
