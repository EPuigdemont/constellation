<x-layouts::auth :title="__('About Constellation')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('About Constellation')"
            :description="__('A private journaling app for capturing memories, notes, and relationships between ideas.')"
        />

        <div class="space-y-4 rounded-xl border border-[var(--theme-border,theme(colors.zinc.200))] bg-[var(--theme-surface,theme(colors.white))] p-5 text-sm text-[var(--theme-text,theme(colors.zinc.800))] dark:border-[var(--theme-border,theme(colors.zinc.700))] dark:bg-[var(--theme-surface,theme(colors.zinc.900))] dark:text-[var(--theme-text,theme(colors.zinc.100))]">
            <section class="space-y-1">
                <h2 class="text-base font-semibold">{{ __('What it does') }}</h2>
                <p>{{ __('Constellation helps you keep diary entries, notes, reminders, images, and visual connections in one private space.') }}</p>
            </section>

            <section class="space-y-1">
                <h2 class="text-base font-semibold">{{ __('Technology') }}</h2>
                <p>{{ __('Built with Laravel, Livewire, Alpine.js, Tailwind CSS, and D3.js.') }}</p>
            </section>

            <section class="space-y-1">
                <h2 class="text-base font-semibold">{{ __('Privacy and access') }}</h2>
                <p>{{ __("Constellation is designed for authenticated users only. Search engine crawling is disabled, and account access is protected by login rate limiting. User data unencrypted. Don't store sensitive information you don't want me to potentially see!") }}</p>
            </section>

            <section class="space-y-1">
                <h2 class="text-base font-semibold">{{ __('Ownership and licensing') }}</h2>
                <p>{{ __('Constellation is an independent solo-developer project, published as source-available software under the PolyForm Noncommercial 1.0.0 license.') }}</p>
                <p>{{ __('Personal use, self-hosting, and modification are permitted. Commercial use and resale are not.') }}</p>
                <p>{{ __('No registered trademark, company, or LLC is associated with this application at this time.') }}</p>
                <p>
                    {{ __('Source code') }}:
                    <a
                        href="https://github.com/EPuigdemont/constellation"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium underline underline-offset-2"
                    >
                        github.com/EPuigdemont/constellation
                    </a>
                </p>
            </section>

            <section class="space-y-1">
                <h2 class="text-base font-semibold">{{ __('Developer contact') }}</h2>
                <p>{{ __('Created and maintained by Enric Puigdemont.') }}</p>
                <p>
                    <a
                        href="https://github.com/EPuigdemont"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium underline underline-offset-2"
                    >
                        github.com/EPuigdemont
                    </a>
                </p>
                <p>
                    <a
                        href="https://www.linkedin.com/in/epuigdemont/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium underline underline-offset-2"
                    >
                        linkedin.com/in/epuigdemont
                    </a>
                </p>
                <p>{{ __('Email') }}: <span class="font-medium">{{ __('enricpuigdemontverger@gmail.com') }}</span></p>
            </section>
        </div>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            @auth
                <flux:link :href="route('diary')" wire:navigate>{{ __('Back to diary') }}</flux:link>
            @else
                <flux:link :href="route('login')" wire:navigate>{{ __('Back to login') }}</flux:link>
            @endauth
        </div>
    </div>
</x-layouts::auth>

