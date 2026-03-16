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
</div>
