@props([
    'sidebar' => false,
])

@php
    $logoSlot = 'flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground cursor-pointer';
@endphp

@if($sidebar)
    <flux:sidebar.brand name="Constellation" {{ $attributes }}>
        <x-slot name="logo" class="{{ $logoSlot }}" x-on:click.prevent.stop="if(document.body.classList.contains('theme-night')){document.documentElement.classList.add('dark')}else{let next=document.documentElement.classList.contains('dark')?'light':'dark';document.documentElement.classList.toggle('dark');window.localStorage.setItem('flux.appearance',next);if(window.Flux)window.Flux.appearance=next}" title="{{ __('Toggle dark mode') }}">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Constellation" {{ $attributes }}>
        <x-slot name="logo" class="{{ $logoSlot }}" x-on:click.prevent.stop="if(document.body.classList.contains('theme-night')){document.documentElement.classList.add('dark')}else{let next=document.documentElement.classList.contains('dark')?'light':'dark';document.documentElement.classList.toggle('dark');window.localStorage.setItem('flux.appearance',next);if(window.Flux)window.Flux.appearance=next}" title="{{ __('Toggle dark mode') }}">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
