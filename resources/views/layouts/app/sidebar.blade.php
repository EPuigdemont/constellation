<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body data-theme-scene class="min-h-screen bg-[var(--theme-bg,#fff)] dark:bg-[var(--theme-bg,theme(colors.zinc.800))] theme-{{ auth()->user()?->theme ?? 'summer' }}">
        <flux:sidebar sticky collapsible class="border-e bg-[var(--theme-sidebar-bg,theme(colors.zinc.50))] border-[var(--theme-sidebar-border,theme(colors.zinc.200))] dark:border-[var(--theme-sidebar-border,theme(colors.zinc.700))] dark:bg-[var(--theme-sidebar-bg,theme(colors.zinc.900))]">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('canvas') }}" wire:navigate />
                <x-theme-icon />
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="book-open" :href="route('diary')" :current="request()->routeIs('diary')" wire:navigate>
                        {{ __('Diary') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('canvas')" :current="request()->routeIs('canvas')" wire:navigate>
                        {{ __('Canvas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="eye" :href="route('vision-board')" :current="request()->routeIs('vision-board')" wire:navigate>
                        {{ __('Vision Board') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="photo" :href="route('images')" :current="request()->routeIs('images')" wire:navigate>
                        {{ __('Images') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <x-theme-switcher />
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                @if(auth()->user()->avatarUrl())
                    <button class="flex items-center gap-1">
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                             class="h-8 w-8 rounded-full object-cover" />
                        <flux:icon name="chevron-down" variant="micro" class="size-4 text-zinc-400" />
                    </button>
                @else
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />
                @endif

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                @if(auth()->user()->avatarUrl())
                                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                                         class="h-8 w-8 rounded-full object-cover" />
                                @else
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />
                                @endif

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Theme particle overlay --}}
        <div data-theme-particles class="pointer-events-none fixed inset-0 z-0 opacity-30"></div>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
