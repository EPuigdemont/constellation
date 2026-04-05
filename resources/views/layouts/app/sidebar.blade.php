<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body data-theme-scene class="min-h-screen bg-[var(--theme-bg,#fff)] dark:bg-[var(--theme-bg,theme(colors.zinc.800))] theme-{{ auth()->user()?->activeTheme() ?? 'summer' }}">
        <flux:sidebar sticky collapsible class="border-e bg-[var(--theme-sidebar-bg,theme(colors.zinc.50))] border-[var(--theme-sidebar-border,theme(colors.zinc.200))] dark:border-[var(--theme-sidebar-border,theme(colors.zinc.700))] dark:bg-[var(--theme-sidebar-bg,theme(colors.zinc.900))]">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('diary') }}" wire:navigate />
                <x-theme-icon />
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <div class="flex items-center justify-between px-3 py-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                    <livewire:notifications-bell />
                </div>

                {{-- Expanded: full sidebar items with labels --}}
                <flux:sidebar.group :heading="__('Platform')" class="grid in-data-flux-sidebar-collapsed-desktop:hidden">
                    <flux:sidebar.item icon="book-open" :href="route('diary')" :current="request()->routeIs('diary')" wire:navigate>
                        {{ __('Diary') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('calendar')" :current="request()->routeIs('calendar')" wire:navigate>
                        {{ __('Calendar') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bell" :href="route('reminders')" :current="request()->routeIs('reminders')" wire:navigate>
                        {{ __('Reminders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('canvas')" :current="request()->routeIs('canvas')" wire:navigate>
                        {{ __('Canvas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="eye" :href="route('vision-board')" :current="request()->routeIs('vision-board')" wire:navigate>
                        {{ __('Vision Board') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="route('constellation')" :current="request()->routeIs('constellation')" wire:navigate>
                        {{ __('Constellation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="photo" :href="route('images')" :current="request()->routeIs('images')" wire:navigate>
                        {{ __('Images') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('friends')" :current="request()->routeIs('friends')" wire:navigate>
                        {{ __('Friends') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Collapsed: icon-only navigation with tooltips --}}
                <div class="not-in-data-flux-sidebar-collapsed-desktop:hidden flex flex-col items-center gap-1 pt-2">
                    <flux:tooltip :content="__('Notifications')" position="right">
                        <div class="flex items-center justify-center rounded-md p-2">
                            <livewire:notifications-bell />
                        </div>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Diary')" position="right">
                        <a href="{{ route('diary') }}" wire:navigate
                           @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('diary'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('diary')])>
                            <flux:icon name="book-open" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Calendar')" position="right">
                        <a href="{{ route('calendar') }}" wire:navigate
                            @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('calendar'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('calendar')])>
                            <flux:icon name="calendar-days" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Reminders')" position="right">
                        <a href="{{ route('reminders') }}" wire:navigate
                            @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('reminders'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('reminders')])>
                            <flux:icon name="bell" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Canvas')" position="right">
                        <a href="{{ route('canvas') }}" wire:navigate
                           @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('canvas'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('canvas')])>
                            <flux:icon name="squares-2x2" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Vision Board')" position="right">
                        <a href="{{ route('vision-board') }}" wire:navigate
                           @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('vision-board'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('vision-board')])>
                            <flux:icon name="eye" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Constellation')" position="right">
                        <a href="{{ route('constellation') }}" wire:navigate
                           @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('constellation'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('constellation')])>
                            <flux:icon name="sparkles" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Images')" position="right">
                        <a href="{{ route('images') }}" wire:navigate
                            @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('images'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('images')])>
                            <flux:icon name="photo" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                    <flux:tooltip :content="__('Friends')" position="right">
                        <a href="{{ route('friends') }}" wire:navigate
                            @class(['flex items-center justify-center rounded-md p-2 transition-colors', 'text-[var(--theme-accent)] bg-[var(--theme-accent)]/10' => request()->routeIs('friends'), 'text-[var(--theme-text-muted)] hover:text-[var(--theme-accent)] hover:bg-[var(--theme-accent)]/5' => !request()->routeIs('friends')])>
                            <flux:icon name="users" variant="outline" class="size-5" />
                        </a>
                    </flux:tooltip>
                </div>
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

            @if(isset($title) && $title)
                <flux:heading size="sm" class="ml-1 truncate text-[var(--theme-text)]">
                    {{ $title }}
                </flux:heading>
            @endif

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

        {{-- Today's notifications banner --}}
        @if (session('today_notifications'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="relative z-20 border-b border-[var(--theme-accent)]/20 px-4 py-2"
                 style="background: color-mix(in srgb, var(--theme-accent) 10%, var(--theme-bg));">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-[var(--theme-text)]">
                        <flux:icon name="bell-alert" variant="solid" class="size-4 text-[var(--theme-accent)]" />
                        @foreach (session('today_notifications') as $n)
                            <span class="inline-flex items-center gap-1 rounded-full border border-[var(--theme-accent)]/20 px-2 py-0.5 text-xs">
                                @if ($n['type'] === 'important_date')
                                    <span>★</span>
                                @else
                                    <span>🔔</span>
                                @endif
                                {{ $n['title'] }}
                            </span>
                        @endforeach
                    </div>
                    <button x-on:click="show = false" class="text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]">
                        <flux:icon name="x-mark" variant="outline" class="size-4" />
                    </button>
                </div>
            </div>
        @endif

        {{-- Global loading overlay to prevent duplicate clicks during Livewire requests --}}
        <div x-data="{
                 syncing: false,
                 init() {
                     Livewire.hook('request', ({ respond, fail }) => {
                         this.syncing = true;
                         respond(() => { this.syncing = false; });
                         fail(() => { this.syncing = false; });
                     });
                 }
             }">
            <div x-show="syncing" x-cloak
                 class="fixed inset-0 z-[99990] cursor-wait"
                 style="background: transparent;">
            </div>
        </div>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
