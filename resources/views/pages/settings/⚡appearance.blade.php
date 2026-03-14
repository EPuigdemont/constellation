<?php

use App\Enums\Theme;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    public string $theme = 'summer';
    public string $language = 'en';

    public function mount(): void
    {
        $this->theme = Auth::user()->theme ?? 'summer';
        $this->language = Auth::user()->language ?? 'en';
    }

    public function updateTheme(string $value): void
    {
        $theme = Theme::tryFrom($value);
        if (! $theme) {
            return;
        }

        $user = Auth::user();
        $user->theme = $theme->value;
        $user->save();

        $this->theme = $theme->value;
        $this->dispatch('theme-updated', theme: $theme->value);
    }

    public function updateLanguage(string $value): void
    {
        if (! in_array($value, ['en', 'es'])) {
            return;
        }

        $user = Auth::user();
        $user->language = $value;
        $user->save();

        $this->language = $value;
        app()->setLocale($value);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>

    <x-pages::settings.layout :heading="__('Theme')" :subheading="__('Choose a color theme for your workspace')" :show-nav="false">
        <div x-data="{
                theme: @entangle('theme'),
                async setTheme(value) {
                    this.theme = value;
                    await $wire.updateTheme(value);
                    window.location.reload();
                }
             }"
             class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
            @foreach(Theme::cases() as $t)
                <button type="button"
                        x-on:click="setTheme('{{ $t->value }}')"
                        :class="theme === '{{ $t->value }}'
                            ? 'ring-2 ring-[{{ $t->swatchColor() }}] ring-offset-2 dark:ring-offset-zinc-900'
                            : 'hover:ring-1 hover:ring-zinc-300 dark:hover:ring-zinc-600'"
                        class="flex flex-col items-center gap-2 rounded-xl border border-zinc-200 p-4 transition-all dark:border-zinc-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full"
                         style="background-color: {{ $t->swatchColor() }}20; color: {{ $t->swatchColor() }};">
                        <flux:icon :name="$t->icon()" variant="outline" class="size-5" />
                    </div>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t->label() }}</span>
                    <div class="flex gap-1">
                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $t->swatchColor() }};"></span>
                        <span class="h-2 w-2 rounded-full opacity-60" style="background-color: {{ $t->swatchColor() }};"></span>
                        <span class="h-2 w-2 rounded-full opacity-30" style="background-color: {{ $t->swatchColor() }};"></span>
                    </div>
                </button>
            @endforeach
        </div>
    </x-pages::settings.layout>

    <x-pages::settings.layout :heading="__('Language')" :subheading="__('Choose your preferred language')" :show-nav="false">
        <flux:radio.group variant="segmented" wire:model.live="language" wire:change="updateLanguage($event.target.value)">
            <flux:radio value="en">English</flux:radio>
            <flux:radio value="es">Español</flux:radio>
        </flux:radio.group>
        <p class="mt-2 text-xs text-[var(--theme-text-muted)]">{{ __('Changes apply immediately after page reload.') }}</p>
    </x-pages::settings.layout>
</section>
