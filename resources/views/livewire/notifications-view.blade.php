@php use App\Enums\ReminderType; @endphp
<div class="page-glitter-wrapper flex h-full flex-col gap-4 p-4 lg:p-6">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->activeTheme() ?? 'summer' }}"></canvas>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-(--theme-text)">{{ __('Notifications') }}</h1>
    </div>

    {{-- Friend Requests --}}
    @if ($friendRequests->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-accent)">{{ __('Friend Requests') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($friendRequests as $friendship)
                    <div class="flex items-center justify-between rounded-lg border border-[var(--theme-accent)] bg-[var(--theme-accent)]/5 p-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]">
                                <flux:icon name="user-plus" variant="outline" class="size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-medium text-[var(--theme-text)]">{{ $friendship->user->name }}</div>
                                <div class="text-xs text-[var(--theme-text-muted)]">{{ $friendship->user->username }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button size="xs" variant="primary" wire:click="acceptFriendRequest('{{ $friendship->id }}')">{{ __('Accept') }}</flux:button>
                            <flux:button size="xs" variant="subtle" wire:click="rejectFriendRequest('{{ $friendship->id }}')">{{ __('Decline') }}</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Items Shared With Me --}}
    @if ($sharedItems->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-accent)">{{ __('Shared With Me') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($sharedItems as $item)
                    <div class="flex items-center justify-between rounded-lg border border-[var(--theme-border)] p-3"
                         style="background: color-mix(in srgb, var(--theme-bg) 95%, var(--theme-accent));">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]">
                                <flux:icon name="share" variant="outline" class="size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-medium text-[var(--theme-text)]">
                                    {{ $item['title'] ?: __('Untitled') }}
                                    <span class="ml-1 rounded-full bg-[var(--theme-accent)]/10 px-1.5 py-0.5 text-[0.6rem] text-[var(--theme-accent)]">{{ $item['type'] }}</span>
                                </div>
                                <div class="text-xs text-[var(--theme-text-muted)]">{{ __('Shared by :name', ['name' => $item['sharer']->name]) }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ $item['url'] }}" wire:navigate
                               class="text-xs font-medium text-[var(--theme-accent)] hover:underline">{{ __('View') }}</a>
                            <flux:button size="xs" variant="subtle" icon="x-mark"
                                         wire:click="dismissSharedItem('{{ $item['share']->id }}')"
                                         wire:confirm="{{ __('Remove this shared item?') }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Approaching Reminders --}}
    @if ($approachingNotifications->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-accent)">{{ __('Approaching') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($approachingNotifications as $notification)
                    <div class="flex items-center gap-3 rounded-lg border border-[var(--theme-accent)]/40 bg-[var(--theme-accent)]/5 p-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]">
                            <flux:icon name="clock" variant="outline" class="size-4" />
                        </div>
                        <div class="text-sm text-[var(--theme-text)]">{{ $notification->data['message'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Today's Important Dates --}}
    @if ($todayDates->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-accent)">{{ __('Today\'s Dates') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($todayDates as $date)
                    <div @class([
                        'flex items-center justify-between rounded-lg border p-3 transition-colors',
                        'border-[var(--theme-border)] opacity-50' => $date->is_done,
                        'border-[var(--theme-accent)] bg-[var(--theme-accent)]/5' => !$date->is_done,
                    ])>
                        <div class="flex items-center gap-3">
                            <button wire:click="toggleDateDone('{{ $date->id }}')"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors {{ $date->is_done ? 'bg-green-500/20 text-green-500' : 'bg-(--theme-accent) text-white' }}">
                                @if ($date->is_done)
                                    <flux:icon name="check" variant="outline" class="size-4"/>
                                @else
                                    <flux:icon name="star" variant="solid" class="size-4"/>
                                @endif
                            </button>
                            <div>
                                <div @class(['text-sm font-medium', 'line-through text-[var(--theme-text-muted)]' => $date->is_done, 'text-[var(--theme-text)]' => !$date->is_done])>
                                    {{ $date->label }}
                                    <span class="ml-1 text-xs text-(--theme-accent)">{{ __('Today!') }}</span>
                                </div>
                                <div class="text-xs text-(--theme-text-muted)">
                                    {{ $date->date->translatedFormat('j F Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pending Reminders --}}
    <div>
        <h2 class="mb-2 text-sm font-semibold text-(--theme-accent)">{{ __('Pending Reminders') }}</h2>
        @if ($pendingReminders->isEmpty())
            <p class="py-4 text-center text-sm text-(--theme-text-muted)">{{ __('No pending reminders.') }}</p>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($pendingReminders as $reminder)
                    <div
                        class="flex items-center justify-between rounded-lg border border-(--theme-accent) bg-(--theme-accent)/5 p-3 transition-colors">
                        <div class="flex items-center gap-3">
                            <button wire:click="toggleReminderDone('{{ $reminder->id }}')"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-(--theme-accent) text-white transition-colors">
                                <flux:icon :name="$reminder->reminder_type?->icon() ?? 'bell'" variant="outline"
                                           class="size-4"/>
                            </button>
                            <div>
                                <div class="text-sm font-medium text-(--theme-text)">
                                    {{ $reminder->title }}
                                    @if ($reminder->reminder_type && $reminder->reminder_type !== ReminderType::General)
                                        <span
                                            class="ml-1 rounded-full bg-(--theme-accent)/10 px-1.5 py-0.5 text-[0.6rem] text-(--theme-accent)">{{ $reminder->reminder_type->label() }}</span>
                                    @endif
                                    <span class="ml-1 text-xs text-(--theme-accent)">{{ __('Due!') }}</span>
                                </div>
                                <div class="text-xs text-(--theme-text-muted)">
                                    {{ $reminder->remind_at->translatedFormat('j F Y, H:i') }}
                                    @if ($reminder->body)
                                        <span class="ml-1">· {{ str($reminder->body)->limit(50) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Upcoming Important Dates --}}
    @if ($upcomingDates->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-text-muted)">{{ __('Upcoming Dates') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($upcomingDates as $date)
                    <div class="flex items-center justify-between rounded-lg border border-(--theme-border) p-3"
                         style="background: color-mix(in srgb, var(--theme-bg) 90%, transparent);">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-(--theme-accent)/10 text-(--theme-accent)">
                                @if ($date->recurs_annually)
                                    <flux:icon name="arrow-path" variant="outline" class="size-4"/>
                                @else
                                    <flux:icon name="calendar" variant="outline" class="size-4"/>
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-medium text-(--theme-text)">{{ $date->label }}</div>
                                <div class="text-xs text-(--theme-text-muted)">
                                    {{ $date->date->translatedFormat('j F') }}
                                    @if ($date->recurs_annually)
                                        <span class="ml-1">· {{ __('Yearly') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recently Completed --}}
    @if ($completedReminders->isNotEmpty())
        <div>
            <h2 class="mb-2 text-sm font-semibold text-(--theme-text-muted)">{{ __('Recently Completed') }}</h2>
            <div class="flex flex-col gap-2">
                @foreach ($completedReminders as $reminder)
                    <div class="flex items-center justify-between rounded-lg border
                    border-(--theme-border) p-3 opacity-50">
                        <div class="flex items-center gap-3">
                            <button wire:click="toggleReminderDone('{{ $reminder->id }}')"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-500/20 text-green-500 transition-colors">
                                <flux:icon name="check" variant="outline" class="size-4"/>
                            </button>
                            <div>
                                <div class="text-sm font-medium text-(--theme-text-muted) line-through">
                                    {{ $reminder->title }}
                                </div>
                                <div class="text-xs text-(--theme-text-muted)">
                                    {{ $reminder->remind_at->translatedFormat('j F Y, H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
