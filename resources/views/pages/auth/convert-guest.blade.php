<x-layouts::auth :title="__('Convert Guest Account')">
    <div class="flex flex-col gap-6">
        <x-auth-language-picker />

        <x-auth-header
            :title="__('Create Your Account')"
            :description="__('Convert your guest account to a full account and keep your data forever!')"
        />

        <div class="bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg p-4 text-sm text-green-900 dark:text-green-100">
            <p class="font-medium">✅ {{ __('Your guest data will be preserved!') }}</p>
            <p class="text-xs mt-1">{{ __('All your diary entries, notes, and settings will be transferred to your new account.') }}</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('guest.convert.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Username -->
            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autocomplete="username"
                placeholder="your-username"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="convert-guest-button">
                    {{ __('Create Account') }}
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <flux:link :href="route('canvas')" wire:navigate class="text-sm font-medium">
                {{ __('Continue as guest') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>

