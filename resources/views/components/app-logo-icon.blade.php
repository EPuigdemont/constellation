@props([
    'variant' => 'auto',
])

@php
    $isLight = $variant === 'light';
    $isDark = $variant === 'dark';
    $isMesh = $variant === 'mesh';
@endphp

@if($isMesh)
{{-- Mesh gradient variant --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    <defs>
        <linearGradient id="mesh-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:var(--theme-accent, #f9a825)" />
            <stop offset="100%" style="stop-color:var(--theme-accent-hover, #f57f17)" />
        </linearGradient>
    </defs>
    {{-- Diary book --}}
    <rect x="6" y="4" width="24" height="32" rx="2" fill="url(#mesh-grad)" opacity="0.9" />
    <rect x="8" y="4" width="2" height="32" fill="currentColor" opacity="0.2" />
    <line x1="14" y1="14" x2="26" y2="14" stroke="currentColor" stroke-width="1" opacity="0.3" />
    <line x1="14" y1="18" x2="24" y2="18" stroke="currentColor" stroke-width="1" opacity="0.3" />
    <line x1="14" y1="22" x2="22" y2="22" stroke="currentColor" stroke-width="1" opacity="0.3" />
    {{-- Star --}}
    <polygon points="32,6 33.5,11 38,11 34.5,14 36,19 32,16 28,19 29.5,14 26,11 30.5,11"
             fill="currentColor" opacity="0.85" />
</svg>
@else
{{-- Default auto variant (uses currentColor, works in light & dark) --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    {{-- Diary book --}}
    <rect x="6" y="4" width="24" height="32" rx="2" fill="currentColor" opacity="0.85" />
    <rect x="8" y="4" width="2" height="32" fill="currentColor" opacity="0.3" />
    {{-- Page lines --}}
    <line x1="14" y1="14" x2="26" y2="14" stroke="currentColor" stroke-width="1" opacity="0.2" />
    <line x1="14" y1="18" x2="24" y2="18" stroke="currentColor" stroke-width="1" opacity="0.2" />
    <line x1="14" y1="22" x2="22" y2="22" stroke="currentColor" stroke-width="1" opacity="0.2" />
    {{-- Star --}}
    <polygon points="32,6 33.5,11 38,11 34.5,14 36,19 32,16 28,19 29.5,14 26,11 30.5,11"
             fill="currentColor" />
</svg>
@endif
