<x-layouts::auth :title="__('Enter as Guest')">
    <div class="flex flex-col gap-6">
        <x-auth-language-picker />

        <x-auth-header
            :title="__('Try Constellation as a Guest')"
            :description="__('Explore most features without creating an account. Demo images are included for the Vision Board, Canvas, and Images pages. Your data will be available for 24 hours.')"
        />

        <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-900 dark:text-blue-100">
            <p class="font-medium mb-2">✨ {{ __('Guest Mode Features:') }}</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <li>{{ __('Create diary entries, notes, and reminders') }}</li>
                <li>{{ __('Organize with the virtual desktop') }}</li>
                <li>{{ __('Use most journaling features') }}</li>
                <li>{{ __('Browse demo images on the vision board') }}</li>
                <li>{{ __('Convert to a full account anytime') }}</li>
            </ul>
        </div>

        <div class="bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-sm text-amber-900 dark:text-amber-100">
            <p class="font-medium mb-1">⏰ {{ __('Note:') }}</p>
            <p>{{ __('Your guest account will expire in 24 hours. You can upgrade to a full account anytime to keep your data forever.') }}</p>
            <p class="mt-2">{{ __('Upgrading to a full account is 100% free.') }}</p>
        </div>

        <form method="POST" action="{{ route('guest.store') }}" class="flex flex-col gap-6">
            @csrf

            @if (app(\App\Services\TurnstileValidationService::class)->enabled())
                <div class="flex flex-col gap-2">
                    <div
                        class="cf-turnstile"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-theme="auto"
                    ></div>
                    @error('cf-turnstile-response')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="guest-enter-button">
                    {{ __('Enter as Guest') }}
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Ready to create an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate class="text-sm font-medium">
                {{ __('Register now') }}
            </flux:link>
        </div>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <flux:link :href="route('login')" wire:navigate class="text-sm font-medium">
                {{ __('Log in to existing account') }}
            </flux:link>
        </div>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <flux:link :href="route('about')" wire:navigate class="text-sm font-medium">
                {{ __('About Constellation') }}
            </flux:link>
        </div>

        @if (app(\App\Services\TurnstileValidationService::class)->enabled())
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
    </div>
</x-layouts::auth>


