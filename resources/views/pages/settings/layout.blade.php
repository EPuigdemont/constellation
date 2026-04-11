@props([
    'heading' => '',
    'subheading' => '',
    'showNav' => true,
])

<div class="flex items-start gap-6 lg:gap-10 max-md:flex-col">
    @if($showNav)
        <div class="w-full shrink-0 pb-2 md:w-55 lg:w-60">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
                @unless(auth()->user()->isGuest())
                    <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
                    <flux:navlist.item :href="route('data.edit')" wire:navigate>{{ __('Data') }}</flux:navlist.item>
                @endunless
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden" />
    @else
        <div class="hidden w-full shrink-0 pb-2 md:block md:w-55 lg:w-60"></div>
    @endif

    <div class="min-w-0 flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-6 w-full max-w-3xl">
            {{ $slot }}
        </div>
    </div>
</div>
