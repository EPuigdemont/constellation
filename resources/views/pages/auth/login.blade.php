<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome to Constellation')" />

        <div x-data="{
                greeting: '',
                init() {
                    const hour = new Date().getHours();
                    if (hour >= 5 && hour < 12) this.greeting = '{{ __('Good morning') }}';
                    else if (hour >= 12 && hour < 17) this.greeting = '{{ __('Good afternoon') }}';
                    else if (hour >= 17 && hour < 21) this.greeting = '{{ __('Good evening') }}';
                    else this.greeting = '{{ __('Good night') }}';
                }
             }"
             class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            <span x-text="greeting + ' ✨'"></span>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Username -->
            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="your-username"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account yet?') }}</span>
            <flux:link :href="route('register')" wire:navigate class="text-sm font-medium">
                {{ __('Register here') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
