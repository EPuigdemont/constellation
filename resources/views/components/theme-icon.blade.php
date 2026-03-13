@php
    $theme = \App\Enums\Theme::tryFrom(auth()->user()?->theme ?? 'summer') ?? \App\Enums\Theme::Summer;
@endphp

<span class="flex items-center" title="{{ $theme->label() }} theme">
    <flux:icon :name="$theme->icon()" variant="outline" class="size-4" style="color: {{ $theme->swatchColor() }};" />
</span>
