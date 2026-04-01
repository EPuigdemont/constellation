<div class="page-glitter-wrapper flex h-screen flex-col overflow-hidden">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->theme ?? 'summer' }}"></canvas>

    <div class="relative z-10 flex items-center gap-3 border-b border-(--theme-border,var(--color-zinc-200)) bg-(--theme-header-bg,var(--color-zinc-50)) px-3 py-2 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-header-bg,var(--color-zinc-900))">
        <flux:heading size="lg">{{ __('Friends') }}</flux:heading>
        <flux:spacer />
        <span class="text-sm text-(--theme-text-muted)">{{ __('Your Friends') }}: {{ $friends->count() }}</span>
    </div>

    <div class="relative z-10 flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="mx-auto w-full max-w-5xl space-y-6">
            <div class="rounded-xl border border-(--theme-border,var(--color-zinc-200)) bg-(--theme-card-bg,var(--color-white)) p-4 sm:p-5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-card-bg,var(--color-zinc-900))">
                <p class="text-sm text-(--theme-text-muted)">{{ __('Manage your friends list. Items shared with friends are only visible to those on this list.') }}</p>
            </div>

            @if($errorMessage)
                <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                    {{ $errorMessage }}
                </div>
            @endif

            @if($successMessage)
                <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                    {{ $successMessage }}
                </div>
            @endif

            <section class="rounded-xl border border-(--theme-border,var(--color-zinc-200)) bg-(--theme-card-bg,var(--color-white)) p-4 sm:p-5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-card-bg,var(--color-zinc-900))">
                <h4 class="text-sm font-semibold text-(--theme-text)">{{ __('Add Friend') }}</h4>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input type="email"
                           wire:model="newFriendEmail"
                           placeholder="{{ __('Enter friend\'s email address...') }}"
                           class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-(--theme-text) dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 sm:flex-1">
                    <flux:button wire:click="addFriend" variant="primary" class="sm:shrink-0">{{ __('Add') }}</flux:button>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-(--theme-border,var(--color-zinc-200)) bg-(--theme-card-bg,var(--color-white)) p-4 sm:p-5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-card-bg,var(--color-zinc-900))">
                    <h4 class="text-sm font-semibold text-(--theme-text)">{{ __('Pending Requests Sent') }}</h4>

                    @if($pendingOutgoing->isNotEmpty())
                        <div class="mt-3 space-y-2">
                            @foreach($pendingOutgoing as $request)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-(--theme-text)">{{ $request->friend->name }}</p>
                                        <p class="truncate text-xs text-(--theme-text-muted)">{{ $request->friend->email }}</p>
                                    </div>
                                    <span class="ml-3 shrink-0 text-xs text-(--theme-text-muted)">{{ __('Pending') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-(--theme-text-muted)">{{ __('No results found.') }}</p>
                    @endif
                </section>

                <section class="rounded-xl border border-(--theme-border,var(--color-zinc-200)) bg-(--theme-card-bg,var(--color-white)) p-4 sm:p-5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-card-bg,var(--color-zinc-900))">
                    <h4 class="text-sm font-semibold text-(--theme-text)">{{ __('Friend Requests') }}</h4>

                    @if($pendingRequests->isNotEmpty())
                        <div class="mt-3 space-y-2">
                            @foreach($pendingRequests as $request)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-(--theme-text)">{{ $request->user->name }}</p>
                                        <p class="truncate text-xs text-(--theme-text-muted)">{{ $request->user->email }}</p>
                                    </div>
                                    <div class="ml-3 flex shrink-0 gap-2">
                                        <flux:button size="sm" wire:click="acceptRequest('{{ $request->id }}')" variant="primary">{{ __('Accept') }}</flux:button>
                                        <flux:button size="sm" wire:click="rejectRequest('{{ $request->id }}')">{{ __('Reject') }}</flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-(--theme-text-muted)">{{ __('No results found.') }}</p>
                    @endif
                </section>
            </div>

            <section class="rounded-xl border border-(--theme-border,var(--color-zinc-200)) bg-(--theme-card-bg,var(--color-white)) p-4 sm:p-5 dark:border-(--theme-border,var(--color-zinc-700)) dark:bg-(--theme-card-bg,var(--color-zinc-900))">
                <h4 class="text-sm font-semibold text-(--theme-text)">{{ __('Your Friends') }} ({{ $friends->count() }})</h4>

                @if($friends->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($friends as $friend)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-(--theme-text)">{{ $friend->name }}</p>
                                    <p class="truncate text-xs text-(--theme-text-muted)">{{ $friend->email }}</p>
                                </div>
                                <flux:button size="sm" variant="danger" wire:click="removeFriend('{{ $friend->id }}')" class="ml-3 shrink-0">{{ __('Remove') }}</flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="text-sm   text-(--theme-text-muted)">{{ __('No friends added yet. Add someone to share items with them.') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

