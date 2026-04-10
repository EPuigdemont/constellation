@php use App\Enums\Mood; @endphp
<div class="page-glitter-wrapper flex h-screen flex-col overflow-hidden md:min-w-187.5">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>
    @if ($limitError !== '')
        <div class="relative z-20 border-b border-(--theme-border,var(--color-zinc-200)) px-3 py-2 text-sm text-(--theme-text,var(--color-zinc-900))"
             style="background: color-mix(in srgb, var(--theme-accent) 12%, var(--theme-bg));">
            {{ $limitError }}
        </div>
    @endif
    {{-- Toolbar --}}
    <div x-data="desktopSearch"
         data-current-user-id="{{ auth()->id() }}"
         class="relative z-10 flex flex-col border-b border-(--theme-border,var(--color-zinc-200)) bg-(--theme-header-bg,var(--color-zinc-50)) dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-header-bg,var(--color-zinc-900))">

        {{-- Row 1: primary toolbar actions --}}
        <div class="flex min-w-0 flex-wrap items-center gap-1 px-2 py-1.5">

            {{-- Mobile: single + button with create dropdown --}}
            <div class="relative md:hidden" x-data="{ mobileCreateOpen: false }">
                <flux:button size="sm" icon="plus" x-on:click="mobileCreateOpen = !mobileCreateOpen"/>
                <div x-show="mobileCreateOpen"
                     x-cloak
                     x-on:click.away="mobileCreateOpen = false"
                     class="absolute left-0 top-full z-50 mt-1 min-w-44 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button"
                            x-on:click="$dispatch('create-entity', { mode: 'diary' }); mobileCreateOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                        </svg>
                        {{ __('Diary Entry') }}
                    </button>
                    <button type="button"
                            x-on:click="$dispatch('create-entity', { mode: 'note' }); mobileCreateOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        {{ __('Note') }}
                    </button>
                    <button type="button"
                            x-on:click="$dispatch('create-entity', { mode: 'postit' }); mobileCreateOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25"/>
                        </svg>
                        {{ __('Post-it') }}
                    </button>
                    <button type="button"
                            x-on:click="document.getElementById('standaloneImageInput').click(); mobileCreateOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                        </svg>
                        {{ __('Image') }}
                    </button>
                    <button type="button"
                            x-on:click="$dispatch('create-entity', { mode: 'reminder' }); mobileCreateOpen = false"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        {{ __('Reminder') }}
                    </button>
                </div>
            </div>

            {{-- Desktop: individual create buttons (hidden on mobile) --}}
            <div class="hidden shrink-0 items-center gap-1 md:flex">
                <flux:button size="sm" x-on:click="$dispatch('create-entity', { mode: 'diary' })"
                             title="{{ __('Diary Entry') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <span class="hidden 2xl:inline">{{ __('Diary Entry') }}</span>
                </flux:button>
                <flux:button size="sm" x-on:click="$dispatch('create-entity', { mode: 'note' })"
                             title="{{ __('Note') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <span class="hidden 2xl:inline">{{ __('Note') }}</span>
                </flux:button>
                <flux:button size="sm" x-on:click="$dispatch('create-entity', { mode: 'postit' })"
                             title="{{ __('Post-it') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25"/>
                    </svg>
                    <span class="hidden 2xl:inline">{{ __('Post-it') }}</span>
                </flux:button>
                <flux:button size="sm" x-on:click="$refs.standaloneImageInput.click()"
                             title="{{ __('Image') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                    </svg>
                    <span class="hidden 2xl:inline">{{ __('Image') }}</span>
                </flux:button>
                <flux:button size="sm" x-on:click="$dispatch('create-entity', { mode: 'reminder' })"
                             title="{{ __('Reminder') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                    <span class="hidden 2xl:inline">{{ __('Reminder') }}</span>
                </flux:button>
            </div>

            <input type="file"
                   id="standaloneImageInput"
                   x-ref="standaloneImageInput"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   class="hidden"
                   wire:model="imageUpload"/>

            {{-- Mobile: search icon toggle (hidden on desktop) --}}
            <button type="button"
                    class="inline-flex items-center rounded-md px-2 py-1.5 text-zinc-700 transition-colors hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700 md:hidden"
                    :class="mobileSearchOpen ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                    x-on:click="mobileSearchOpen = !mobileSearchOpen; $nextTick(() => { if (mobileSearchOpen && $refs.mobileSearchInput) $refs.mobileSearchInput.focus() })"
                    title="{{ __('Search') }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </button>

            {{-- Desktop: search text input (hidden on mobile) --}}
            <div class="relative hidden md:block">
                <input type="text"
                       x-model="searchQuery"
                       x-on:input.debounce.250ms="filterCards()"
                       placeholder="{{ __('Search cards...') }}"
                       class="w-28 rounded-lg border border-zinc-200 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 lg:w-36">
                <button
                    x-show="searchQuery !== '' || activeTagFilter !== null || activeTypeFilters.length < allTypes.length || !showShared"
                    x-on:click="clearFilters()"
                    x-cloak
                    type="button"
                    class="absolute inset-y-0 right-1 flex items-center px-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    title="{{ __('Clear filters') }}">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Entity type multi-select filter (icons) — desktop only --}}
            <div
                class="hidden items-center gap-0.5 rounded-md border border-zinc-200 bg-white p-0.5 dark:border-zinc-700 dark:bg-zinc-800 md:flex">
                <button type="button"
                        x-on:click="toggleType('diary_entry')"
                        :class="isTypeActive('diary_entry') ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                        class="rounded p-1.5 text-zinc-700 transition-opacity dark:text-zinc-300"
                        title="{{ __('Diary Entries') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                    </svg>
                </button>
                <button type="button"
                        x-on:click="toggleType('note')"
                        :class="isTypeActive('note') ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                        class="rounded p-1.5 text-zinc-700 transition-opacity dark:text-zinc-300"
                        title="{{ __('Notes') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </button>
                <button type="button"
                        x-on:click="toggleType('postit')"
                        :class="isTypeActive('postit') ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                        class="rounded p-1.5 text-zinc-700 transition-opacity dark:text-zinc-300"
                        title="{{ __('Post-its') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25"/>
                    </svg>
                </button>
                <button type="button"
                        x-on:click="toggleType('image')"
                        :class="isTypeActive('image') ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                        class="rounded p-1.5 text-zinc-700 transition-opacity dark:text-zinc-300"
                        title="{{ __('Images') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                    </svg>
                </button>
            </div>

            {{-- Tag filter (visible on all screen sizes) --}}
            <div class="relative" x-data="{ tagFilterOpen: false }">
                <button type="button"
                        x-on:click="tagFilterOpen = !tagFilterOpen"
                        :class="activeTagFilters.length > 0 ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Filter by Tag') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                    </svg>
                </button>
                <div x-show="tagFilterOpen"
                     x-on:click.away="tagFilterOpen = false"
                     x-cloak
                     class="absolute right-0 z-50 mt-1 max-h-48 w-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button"
                            x-on:click="clearTagFilters()"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            :class="activeTagFilters.length === 0 ? 'font-semibold' : ''">
                        <span class="inline-flex w-4 items-center justify-center">
                            <svg x-show="activeTagFilters.length === 0" x-cloak class="size-3.5 text-zinc-600 dark:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                        {{ __('All Tags') }}
                    </button>
                    @foreach($filterAvailableTags as $tag)
                        <button type="button"
                                x-on:click="toggleTagFilter('{{ $tag['id'] }}')"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                :class="isTagActive('{{ $tag['id'] }}') ? 'font-semibold' : ''">
                            <span class="inline-flex w-4 items-center justify-center">
                                <svg x-show="isTagActive('{{ $tag['id'] }}')" x-cloak class="size-3.5 text-zinc-600 dark:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </span>
                            {{ $tag['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Toggle shared elements visibility (visible on all screen sizes) --}}
            <button type="button"
                    x-on:click="toggleShared()"
                    :class="showShared ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                    class="rounded p-1.5 text-zinc-700 transition-opacity dark:text-zinc-300"
                    title="{{ __('Show shared') }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9 9 0 0 1 3 12c0-1.47.353-2.856.978-4.082"/>
                </svg>
            </button>

            <span class="hidden h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600 lg:block"></span>

            {{-- Canvas view toggles --}}
            <div x-data="desktopToggles" class="flex shrink-0 flex-wrap items-center gap-1">
                <button type="button" x-on:click="toggleAutoArrangeGrid()"
                        :class="autoArrangeGrid ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Auto Arrange as Grid') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <rect x="3" y="3" width="8" height="8" rx="1.5"/>
                        <rect x="13" y="3" width="8" height="8" rx="1.5"/>
                        <rect x="3" y="13" width="8" height="8" rx="1.5"/>
                        <rect x="13" y="13" width="8" height="8" rx="1.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M18.5 7h-3m0 0 1.25-1.25M15.5 7l1.25 1.25"/>
                    </svg>
                </button>
                <button type="button" x-on:click="toggleGridBackground()"
                        :class="showGridBackground ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="inline-flex items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300"
                        title="{{ __('Show Grid Background') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/>
                    </svg>
                </button>
                {{-- Guide lines: hidden on mobile --}}
                <button type="button" x-on:click="toggleGuides()"
                        :class="showGuides ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="hidden items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300 md:inline-flex"
                        title="{{ __('Show Guide Lines') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                    </svg>
                </button>
                {{-- Snap to grid: hidden on mobile --}}
                <button type="button" x-on:click="toggleSnap()"
                        :class="snapToGrid ? 'bg-zinc-200 dark:bg-zinc-700' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                        class="hidden items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300 md:inline-flex"
                        title="{{ __('Snap to Grid') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5"/>
                    </svg>
                </button>
                {{-- Widgets: hidden on mobile --}}
                <button type="button" x-on:click="toggleWidgets()"
                        :class="showWidgets ? 'bg-zinc-200 dark:bg-zinc-700' : 'opacity-40 hover:opacity-70'"
                        class="hidden items-center rounded-md px-2 py-1.5 text-sm text-zinc-700 dark:text-zinc-300 md:inline-flex"
                        title="{{ __('Widgets') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L12 12.75l-5.571-3m11.142 0L21.75 12l-4.179 2.25m0 0L12 17.25l-5.571-3m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25"/>
                    </svg>
                </button>

                <span class="mx-1 hidden h-5 w-px bg-zinc-300 dark:bg-zinc-600 md:block"></span>

                <flux:button size="sm" x-on:click="$dispatch('center-canvas')" title="{{ __('Center Canvas') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                    </svg>
                </flux:button>
                <flux:button size="sm" x-on:click="$dispatch('zoom-to-fit')" title="{{ __('Zoom to Fit') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                    </svg>
                </flux:button>
            </div>

            <span class="hidden h-5 w-px shrink-0 bg-zinc-300 dark:bg-zinc-600 lg:block"></span>

            {{-- Zoom controls: hidden on mobile --}}
            <div x-data="desktopZoom" class="ml-auto hidden shrink-0 items-center gap-2 md:flex">
                <flux:button size="sm" icon="minus" x-on:click="zoomOut"/>
                <span class="w-12 text-center text-sm text-zinc-600 dark:text-zinc-400"
                      x-text="Math.round(zoom * 100) + '%'"></span>
                <flux:button size="sm" icon="plus" x-on:click="zoomIn"/>
            </div>
        </div>

        {{-- Row 2 (mobile only): search input, shown when search icon is toggled --}}
        <div class="flex items-center gap-2 px-2 pb-1.5 md:hidden"
             x-show="mobileSearchOpen"
             x-cloak>
            <div class="relative flex-1">
                <input type="text"
                       x-model="searchQuery"
                       x-on:input.debounce.250ms="filterCards()"
                       x-ref="mobileSearchInput"
                       placeholder="{{ __('Search cards...') }}"
                       class="w-full rounded-lg border border-zinc-200 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <button
                    x-show="searchQuery !== '' || activeTagFilter !== null || activeTypeFilters.length < allTypes.length || !showShared"
                    x-on:click="clearFilters()"
                    x-cloak
                    type="button"
                    class="absolute inset-y-0 right-1 flex items-center px-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    title="{{ __('Clear filters') }}">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Linking mode banner --}}
    <div x-show="Alpine.store('desktop').linkingMode !== ''"
         x-cloak
         class="flex items-center justify-between gap-3 border-b px-4 py-2"
         :class="Alpine.store('desktop').linkingMode === 'attach'
             ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/30'
             : 'border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-900/30'">
        <span class="text-sm font-medium"
              :class="Alpine.store('desktop').linkingMode === 'attach'
                  ? 'text-blue-700 dark:text-blue-300'
                  : 'text-purple-700 dark:text-purple-300'"
              x-text="Alpine.store('desktop').linkingMode === 'attach'
                  ? '{{ __('Click a card to attach to it as parent') }}'
                  : '{{ __('Click a card to link as sibling') }}'">
        </span>
        <flux:button size="sm" variant="ghost" wire:click="cancelLinking">{{ __('Cancel') }}</flux:button>
    </div>

    {{-- Empty state for first-time users --}}
    @if(count($cards) === 0)
        <div class="flex flex-1 flex-col items-center justify-center gap-4 p-8">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-(--theme-accent)/10">
                <flux:icon name="sparkles" variant="outline" class="size-8 text-(--theme-accent)"/>
            </div>
            <div class="text-center">
                <h2 class="text-lg font-semibold text-(--theme-text)">{{ __('Canvas — your creative workspace') }}</h2>
                <p class="mt-1 text-sm text-(--theme-text-muted)">{{ __('Create your first entry using the buttons above.') }}</p>
            </div>
        </div>
    @endif

    {{-- Canvas container --}}
    <div class="relative flex-1 overflow-auto {{ count($cards) === 0 ? 'hidden' : '' }}"
         id="desktop-viewport"
         x-data="desktopViewport"
         x-on:scroll="updateScroll()">
        <div wire:ignore
             x-data="{ get zoom() { return Alpine.store('desktop').zoom } }"
             :style="'transform: scale(' + zoom + '); transform-origin: 0 0; width: 4000px; height: 4000px;'"
             :class="Alpine.store('desktop').showGridBackground ? 'desktop-grid-bg' : ''"
             class="relative"
             id="desktop-canvas"
             x-on:contextmenu.prevent="$dispatch('desktop-context', { x: $event.clientX, y: $event.clientY })">

            {{-- Guide lines (rendered by JS) --}}
            <div id="desktop-guides" class="pointer-events-none absolute inset-0" style="z-index: 99998;"></div>

            @foreach($cards as $index => $card)
                <div wire:key="card-{{ $card['id'] }}"
                     data-card-id="{{ $card['id'] }}"
                     data-card-type="{{ $card['type'] }}"
                     data-grid-item="entity"
                     data-grid-key="{{ $card['type'] }}:{{ $card['id'] }}"
                     x-data="desktopCard({{ Js::from(array_merge($card, ['is_owner' => $card['owner_id'] === auth()->id()])) }})"
                     x-init="initDrag()"
                     :style="'position: absolute; left: ' + cardX + 'px; top: ' + cardY + 'px; z-index: ' + cardZ + ';{{ $card['color_override'] ? ' background-color: ' . $card['color_override'] . ';' : '' }}' + (cardW ? ' width: ' + cardW + 'px;' : '') + (cardH ? ' height: ' + cardH + 'px;' : '')"
                     x-on:contextmenu.prevent.stop="$dispatch('desktop-context', {
                         x: $event.clientX,
                         y: $event.clientY,
                         entityId: '{{ $card['id'] }}',
                         entityType: '{{ $card['type'] }}',
                         isOwner: {{ $card['owner_id'] === auth()->id() ? 'true' : 'false' }},
                         isHidden: {{ !empty($card['is_hidden']) ? 'true' : 'false' }},
                         mood: '{{ $card['mood'] ?? 'plain' }}',
                         hasParent: {{ !empty($card['parent_id']) ? 'true' : 'false' }}
                     })"
                     class="desktop-card {{ $card['mood'] ? 'mood-' . $card['mood'] : 'mood-plain' }} card-type-{{ $card['type'] }} touch-none select-none {{ !empty($card['is_hidden']) ? 'desktop-card-hidden' : '' }}">
                    <x-desktop.entity-card :card="$card"/>
                </div>
            @endforeach

            <x-desktop.diary-notebook/>
            <x-desktop.vision-board-widget/>
        </div>
    </div>

    {{-- Trashcan Drop Zone --}}
    <div id="desktop-trashcan"
         class="desktop-trashcan">
        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
             stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
        </svg>
        <span class="text-xs">{{ __('Drop to delete') }}</span>
    </div>

    {{-- Context Menu --}}
    <div x-data="desktopContextMenu"
         x-show="open"
         x-on:desktop-context.window="openMenu($event.detail)"
         x-on:click.window="close()"
         x-on:keydown.escape.window="close()"
         x-cloak
         :style="'position: fixed; left: ' + menuX + 'px; top: ' + menuY + 'px; z-index: 9999;'"
         class="min-w-48 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">

        <template x-if="entityId">
            <div>
                <template x-if="isOwner">
                    <div>
                        <button x-on:click="edit()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            {{ __('Edit') }}
                        </button>

                        {{-- Share with submenu --}}
                        <div class="relative" x-data="{ shareOpen: false }">
                            <button x-on:click.stop="shareOpen = !shareOpen; $wire.loadFriendsForSharing(); $wire.loadCurrentShares(entityId, entityType)"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                {{ __('Share with') }}
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                </svg>
                            </button>
                            <div x-show="shareOpen" x-cloak
                                 class="absolute left-full top-0 ml-1 min-w-48 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900 max-h-48 overflow-y-auto">
                                <template x-if="$wire.userFriends.length === 0">
                                    <div class="px-3 py-1.5 text-sm text-zinc-400">
                                        {{ __('No friends to share with') }}
                                    </div>
                                </template>
                                <template x-if="$wire.userFriends.length > 0">
                                    <template x-for="friend in $wire.userFriends" :key="friend.id">
                                        <button type="button"
                                                x-on:click.stop="$wire.toggleShareWithFriend(entityId, entityType, friend.id)"
                                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                            <span class="inline-flex w-4 items-center justify-center">
                                                <svg x-show="$wire.currentEntitySharedFriends.includes(String(friend.id))" x-cloak class="size-3.5 text-zinc-600 dark:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </span>
                                            <span x-text="'@' + friend.username"></span>
                                        </button>
                                    </template>
                                </template>
                            </div>
                        </div>

                        <button x-on:click="toggleHidden()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <span
                                x-text="isHidden ? '{{ __('Show on Canvas') }}' : '{{ __('Hide from Canvas') }}'"></span>
                        </button>

                        {{-- Mood submenu --}}
                        <div class="relative" x-data="{ moodOpen: false }">
                            <button x-on:click.stop="moodOpen = !moodOpen"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                {{ __('Change Mood') }}
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                </svg>
                            </button>
                            <div x-show="moodOpen" x-cloak
                                 class="absolute left-full top-0 ml-1 min-w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach(Mood::cases() as $mood)
                                    <button x-on:click="changeMood('{{ $mood->value }}')"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span
                                            class="inline-block size-3 rounded-full mood-dot-{{ $mood->value }}"></span>
                                        {{ ucfirst($mood->value) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

                         {{-- Relationship actions --}}
                        {{--<button x-on:click="attachTo()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" /></svg>
                            {{ __('Attach to…') }}
                        </button>
                        <button x-on:click="linkSibling()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.556a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.69" /></svg>
                            {{ __('Link as Sibling…') }}
                        </button>
                        <template x-if="hasParent">
                            <button x-on:click="detach()"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                {{ __('Detach from Parent') }}
                            </button>
                        </template>--}}

                        <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>

                        <button x-on:click="deleteEntity()"
                                class="flex w-full items-center gap-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </template>

                <template x-if="!isOwner">
                    <div class="px-3 py-1.5 text-sm text-zinc-400">
                        {{ __('Shared entity (read-only)') }}
                    </div>
                </template>
            </div>
        </template>

        <template x-if="!entityId">
            <div class="px-3 py-1.5 text-sm text-zinc-400">
                {{ __('Right-click a card for options') }}
            </div>
        </template>
    </div>

    {{-- Editor Modal --}}
    <flux:modal wire:model="showEditorModal" class="w-full max-w-3xl" flyout>
        <div class="desktop-editor-modal space-y-3 sm:space-y-5" x-data="tiptapEditor" x-on:keydown.escape.window="syncToWire()"
             :class="'mood-' + ($wire.editorMood || 'plain')"
             :style="$wire.editorColorOverride ? 'background-color: ' + $wire.editorColorOverride : ''"
             x-effect="
                 if ($wire.editingEntityId && $wire.editorMood) {
                     window.dispatchEvent(new CustomEvent('mood-preview', {
                         detail: { entityId: $wire.editingEntityId, mood: $wire.editorMood, colorOverride: $wire.editorColorOverride }
                     }));
                 }
             ">
            <flux:heading size="lg">
                <span x-text="$wire.editingEntityId ? '{{ __('Edit') }}' : '{{ __('New') }}'"></span>
                <span
                    x-text="$wire.editorMode === 'diary' ? '{{ __('Diary Entry') }}' : ($wire.editorMode === 'postit' ? '{{ __('Post-it') }}' : ($wire.editorMode === 'reminder' ? '{{ __('Reminder') }}' : ($wire.editorMode === 'image' ? '{{ __('Image') }}' : '{{ __('Note') }}')))"></span>
            </flux:heading>

            @if($editorMode === 'diary' || $editorMode === 'note' || $editorMode === 'reminder' || $editorMode === 'image')
                <flux:field>
                    <flux:label>{{ $editorMode === 'reminder' ? __('Reminder title') : __('Title') }}</flux:label>
                    <flux:input wire:model="editorTitle" placeholder="{{ $editorMode === 'image' ? __('Enter image title...') : __('Enter title...') }}"/>
                </flux:field>
            @endif

            @if($editorMode === 'reminder')
                <flux:field>
                    <flux:label>{{ __('Remind at') }}</flux:label>
                    <input type="datetime-local" wire:model="editorRemindAt"
                           class="w-full rounded-md border border-(--theme-border) bg-(--theme-bg) px-3 py-1.5 text-sm text-(--theme-text) focus:border-(--theme-accent) focus:outline-none"/>
                </flux:field>
            @endif

            @if($editorMode !== 'image')
                {{-- Tiptap Toolbar (hidden for now) --}}
                <div
                    class="hidden flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-(--card-border,var(--color-zinc-200)) bg-(--card-bg,var(--color-zinc-50)) px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-900">
                <button type="button" x-on:click="toggleBold()"
                        :class="isActive('bold') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Bold') }}">B
                </button>
                <button type="button" x-on:click="toggleItalic()"
                        :class="isActive('italic') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm italic hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Italic') }}">I
                </button>
                <button type="button" x-on:click="toggleUnderline()"
                        :class="isActive('underline') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm underline hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Underline') }}">U
                </button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="setHeading(1)"
                        :class="isActive('heading', {level: 1}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Heading 1') }}">H1
                </button>
                <button type="button" x-on:click="setHeading(2)"
                        :class="isActive('heading', {level: 2}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Heading 2') }}">H2
                </button>
                <button type="button" x-on:click="setHeading(3)"
                        :class="isActive('heading', {level: 3}) ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm font-bold hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Heading 3') }}">H3
                </button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="toggleBulletList()"
                        :class="isActive('bulletList') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Bullet List') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </button>
                <button type="button" x-on:click="toggleOrderedList()"
                        :class="isActive('orderedList') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Ordered List') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.242 5.992h12m-12 6.003h12m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H4.372m-3.279 7.561c.282-.533.66-.89 1.089-1.075.293-.126.628-.193.961-.186.593.012 1.126.277 1.37.76.257.508.035 1.119-.488 1.597l-2.066 1.884h2.736M2.98 17.243h.045"/>
                    </svg>
                </button>
                <button type="button" x-on:click="toggleBlockquote()"
                        :class="isActive('blockquote') ? 'bg-zinc-200 dark:bg-zinc-700' : ''"
                        class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Blockquote') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                    </svg>
                </button>

                <span class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></span>

                <button type="button" x-on:click="insertImage()"
                        class="rounded px-2 py-1 text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700"
                        title="{{ __('Insert Image') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Zm16.5-13.5h.008v.008h-.008V7.5Zm0 0a.375.375 0 1 0-.75 0 .375.375 0 0 0 .75 0Z"/>
                    </svg>
                </button>
                <input type="file" x-ref="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden"
                       x-on:change="handleImageSelect($event)"/>
                </div>

                {{-- Tiptap Editor Content --}}
                <div wire:ignore
                     class="tiptap-editor-content min-h-48 rounded-lg border border-(--card-border,var(--color-zinc-200)) p-3 dark:border-zinc-700">
                    <div x-ref="editorElement"></div>
                </div>

                {{-- Metadata Row --}}
                <div class="grid grid-cols-1 gap-5 {{ $editorMood === 'custom' ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                {{-- Mood --}}
                <flux:field>
                    <flux:label>{{ __('Mood') }}</flux:label>
                    <flux:select wire:model.live="editorMood">
                        @foreach(Mood::cases() as $mood)
                            <flux:select.option
                                value="{{ $mood->value }}">{{ ucfirst($mood->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Color Override (only shown when mood is "custom") --}}
                @if($editorMood === 'custom')
                    <flux:field>
                        <flux:label>{{ __('Color') }}</flux:label>
                        <div class="flex items-center gap-2">
                            <input type="color"
                                   wire:model.live="editorColorOverride"
                                   class="h-9 w-12 cursor-pointer rounded border border-zinc-200 dark:border-zinc-700"
                                   title="{{ __('Custom color override') }}">
                            @if($editorColorOverride)
                                <button type="button"
                                        wire:click="$set('editorColorOverride', null)"
                                        class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                                        title="{{ __('Reset color') }}">
                                    {{ __('Reset') }}
                                </button>
                            @endif
                        </div>
                    </flux:field>
                @endif

                {{-- Tags --}}
                <flux:field>
                    <flux:label>{{ __('Tags') }}</flux:label>
                    <div x-data="{ tagDropdownOpen: false }" class="relative">
                        {{-- Selected tags as pills --}}
                        <div class="mb-1 flex flex-wrap gap-1">
                            @foreach($availableTags as $tag)
                                @if(in_array($tag['id'], $editorTagIds, true))
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-zinc-200 px-2 py-0.5 text-xs dark:bg-zinc-700">
                                        {{ $tag['name'] }}
                                        <button type="button" wire:click="toggleTag('{{ $tag['id'] }}')"
                                                class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        {{-- Search input --}}
                        <input type="text"
                               wire:model.live.debounce.300ms="tagSearch"
                               x-on:focus="tagDropdownOpen = true"
                               x-on:click.away="tagDropdownOpen = false"
                               placeholder="{{ __('Search or create tag...') }}"
                               class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">

                        {{-- Dropdown --}}
                        <div x-show="tagDropdownOpen" x-cloak
                             class="absolute z-50 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                            @php
                                $filteredTags = collect($availableTags)->filter(function ($tag) {
                                    if ($this->tagSearch === '') return true;
                                    return str_contains(strtolower($tag['name']), strtolower($this->tagSearch));
                                });
                            @endphp

                            @forelse($filteredTags as $tag)
                                <button type="button"
                                        wire:click="toggleTag('{{ $tag['id'] }}')"
                                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    @if(in_array($tag['id'], $editorTagIds, true))
                                        <svg class="size-4 text-green-500" xmlns="http://www.w3.org/2000/svg"
                                             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    @else
                                        <span class="size-4"></span>
                                    @endif
                                    {{ $tag['name'] }}
                                </button>
                            @empty
                                @if($tagSearch !== '')
                                    <button type="button"
                                            wire:click="createTagInline('{{ addslashes($tagSearch) }}')"
                                            x-on:click="tagDropdownOpen = false"
                                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:text-blue-400 dark:hover:bg-zinc-800">
                                        + {{ __('Create') }} "{{ $tagSearch }}"
                                    </button>
                                @else
                                    <div class="px-3 py-1.5 text-sm text-zinc-400">{{ __('No tags available') }}</div>
                                @endif
                            @endforelse

                            @if($tagSearch !== '' && $filteredTags->isNotEmpty())
                                @php
                                    $exactMatch = $filteredTags->contains(fn ($t) => strtolower($t['name']) === strtolower($tagSearch));
                                @endphp
                                @unless($exactMatch)
                                    <button type="button"
                                            wire:click="createTagInline('{{ addslashes($tagSearch) }}')"
                                            x-on:click="tagDropdownOpen = false"
                                            class="flex w-full items-center gap-2 border-t border-zinc-200 px-3 py-1.5 text-left text-sm text-blue-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-blue-400 dark:hover:bg-zinc-800">
                                        + {{ __('Create') }} "{{ $tagSearch }}"
                                    </button>
                                @endunless
                            @endif
                        </div>
                    </div>
                </flux:field>
                </div>
            @endif

            <div class="flex items-center justify-between">
                @if($editingEntityId !== '')
                    <div x-data="{ confirmDelete: false }">
                        <flux:button x-show="!confirmDelete" variant="danger" size="sm"
                                     x-on:click="confirmDelete = true">
                            {{ __('Delete') }}
                        </flux:button>
                        <div x-show="confirmDelete" x-cloak class="flex items-center gap-2">
                            <span class="text-sm text-red-600 dark:text-red-400">{{ __('Are you sure?') }}</span>
                            <flux:button variant="danger" size="sm"
                                         wire:click="deleteFromEditor">{{ __('Yes, delete') }}</flux:button>
                            <flux:button size="sm" x-on:click="confirmDelete = false">{{ __('No') }}</flux:button>
                        </div>
                    </div>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <flux:button
                        x-on:click="syncToWire(); $wire.showEditorModal = false">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" x-on:click="syncToWire()"
                                 wire:click="saveEditor">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    {{-- Read-only Shared Entity Modal --}}
    <flux:modal wire:model="showReadonlyModal" class="w-full max-w-2xl" flyout>
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Shared entity (read-only)') }}</flux:heading>
                    <p class="mt-1 text-sm text-(--theme-text-muted)">
                        @if($readonlyOwnerUsername !== '')
                            {{ '@' . $readonlyOwnerUsername }}
                        @endif
                        @if($readonlyUpdatedAt !== '')
                            <span class="ml-2">{{ $readonlyUpdatedAt }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($readonlyEntityType !== 'image' && $readonlyTitle !== '')
                <div>
                    <h3 class="text-base font-semibold text-(--theme-text)">{{ $readonlyTitle }}</h3>
                </div>
            @endif

            @if($readonlyEntityType === 'image' && $readonlyImageUrl !== '')
                <div class="overflow-hidden rounded-lg border border-(--theme-border)">
                    <img src="{{ $readonlyImageUrl }}" alt="{{ $readonlyBody ?: __('Image') }}"
                         class="max-h-[60vh] w-full object-contain"/>
                </div>
            @endif

            @if($readonlyBody !== '')
                <div
                    class="max-h-[40vh] overflow-auto rounded-lg border border-(--theme-border) bg-(--theme-bg) p-3 text-sm text-(--theme-text)">
                    @if($readonlyEntityType === 'image')
                        <p>{{ $readonlyBody }}</p>
                    @else
                        <div class="prose prose-sm max-w-none dark:prose-invert">{!! $readonlyBody !!}</div>
                    @endif
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="closeReadonlyModal">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Upload overlay --}}
    <div wire:loading.flex wire:target="imageUpload, uploadImage"
         class="fixed inset-0 z-99999 items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
            <svg class="size-10 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-white">{{ __('Uploading image...') }}</span>
        </div>
    </div>
</div>
