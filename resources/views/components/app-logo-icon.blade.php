@props([
    'variant' => 'auto',
])

@php
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
    {{-- Large star (top-left) --}}
    <path d="M14 4 L16 11 L23 13 L16 15 L14 22 L12 15 L5 13 L12 11 Z" fill="url(#mesh-grad)" />
    {{-- Medium star (right) --}}
    <path d="M30 14 L31.5 19 L36 20.5 L31.5 22 L30 27 L28.5 22 L24 20.5 L28.5 19 Z" fill="url(#mesh-grad)" opacity="0.8" />
    {{-- Small star (bottom-left) --}}
    <path d="M11 28 L12 31.5 L15.5 32.5 L12 33.5 L11 37 L10 33.5 L6.5 32.5 L10 31.5 Z" fill="url(#mesh-grad)" opacity="0.6" />
    {{-- Connecting lines --}}
    <line x1="14" y1="13" x2="30" y2="20.5" stroke="url(#mesh-grad)" stroke-width="0.5" opacity="0.3" />
    <line x1="14" y1="13" x2="11" y2="32.5" stroke="url(#mesh-grad)" stroke-width="0.5" opacity="0.3" />
    <line x1="30" y1="20.5" x2="11" y2="32.5" stroke="url(#mesh-grad)" stroke-width="0.5" opacity="0.3" />
</svg>
@else
{{-- Default auto variant (uses currentColor, works in light & dark) --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    {{-- Large star (top-left) --}}
    <path d="M14 4 L16 11 L23 13 L16 15 L14 22 L12 15 L5 13 L12 11 Z" fill="currentColor" />
    {{-- Medium star (right) --}}
    <path d="M30 14 L31.5 19 L36 20.5 L31.5 22 L30 27 L28.5 22 L24 20.5 L28.5 19 Z" fill="currentColor" opacity="0.75" />
    {{-- Small star (bottom-left) --}}
    <path d="M11 28 L12 31.5 L15.5 32.5 L12 33.5 L11 37 L10 33.5 L6.5 32.5 L10 31.5 Z" fill="currentColor" opacity="0.5" />
    {{-- Connecting lines --}}
    <line x1="14" y1="13" x2="30" y2="20.5" stroke="currentColor" stroke-width="0.5" opacity="0.2" />
    <line x1="14" y1="13" x2="11" y2="32.5" stroke="currentColor" stroke-width="0.5" opacity="0.2" />
    <line x1="30" y1="20.5" x2="11" y2="32.5" stroke="currentColor" stroke-width="0.5" opacity="0.2" />
</svg>
@endif
