<div class="page-glitter-wrapper flex h-full flex-col gap-4 p-4 lg:p-6">
    <canvas class="page-glitter" data-glitter-theme="{{ auth()->user()?->theme ?? 'summer' }}"></canvas>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-[var(--theme-text)]">{{ __('Reminders & Dates') }}</h1>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-lg border border-[var(--theme-border)] p-1" style="background: color-mix(in srgb, var(--theme-bg-secondary) 50%, transparent);">
        <button wire:click="$set('tab', 'dates')"
                @class(['rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                    'bg-[var(--theme-accent)] text-white' => $tab === 'dates',
                    'text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]' => $tab !== 'dates'])>
            {{ __('Important Dates') }}
        </button>
        <button wire:click="$set('tab', 'reminders')"
                @class(['rounded-md px-4 py-1.5 text-sm font-medium transition-colors',
                    'bg-[var(--theme-accent)] text-white' => $tab === 'reminders',
                    'text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]' => $tab !== 'reminders'])>
            {{ __('Reminders') }}
        </button>
    </div>

    {{-- Important Dates Tab --}}
    @if ($tab === 'dates')
        <div class="flex items-center justify-between">
            <p class="text-sm text-[var(--theme-text-muted)]">{{ __('Birthdays, anniversaries, and special days') }}</p>
            <flux:button size="sm" icon="plus" wire:click="openDateForm">{{ __('Add') }}</flux:button>
        </div>

        @if ($showDateForm)
            <div class="rounded-lg border border-[var(--theme-accent)]/30 p-4"
                 style="background: color-mix(in srgb, var(--theme-bg) 95%, var(--theme-accent));">
                <div class="mb-3 text-sm font-medium text-[var(--theme-accent)]">
                    {{ $editingDateId ? __('Edit Date') : __('New Important Date') }}
                </div>
                <div class="flex flex-col gap-3">
                    <input type="text" wire:model="dateLabel" placeholder="{{ __('Label (e.g. Birthday, Anniversary)') }}"
                           class="w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] placeholder-[var(--theme-text-muted)] focus:border-[var(--theme-accent)] focus:outline-none" />
                    @error('dateLabel') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                    <input type="date" wire:model="dateValue"
                           class="w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] focus:border-[var(--theme-accent)] focus:outline-none" />
                    @error('dateValue') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                    <label class="flex items-center gap-2 text-sm text-[var(--theme-text)]">
                        <input type="checkbox" wire:model="dateRecurs" class="rounded border-[var(--theme-border)] accent-[var(--theme-accent)]" />
                        {{ __('Recurs annually') }}
                    </label>
                    <div class="flex justify-end gap-2">
                        <flux:button size="xs" variant="subtle" wire:click="closeDateForm">{{ __('Cancel') }}</flux:button>
                        <flux:button size="xs" variant="primary" wire:click="saveDate" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveDate">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveDate">…</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-2">
            @forelse ($importantDates as $date)
                @php
                    $isToday = $date->date->month === now()->month && $date->date->day === now()->day;
                @endphp
                <div @class([
                    'flex items-center justify-between rounded-lg border p-3 transition-colors',
                    'border-[var(--theme-accent)] bg-[var(--theme-accent)]/5' => $isToday,
                    'border-[var(--theme-border)]' => !$isToday,
                ]) style="background: {{ $isToday ? '' : 'color-mix(in srgb, var(--theme-bg) 90%, transparent)' }};">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $isToday ? 'bg-[var(--theme-accent)] text-white' : 'bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]' }}">
                            @if ($date->recurs_annually)
                                <flux:icon name="arrow-path" variant="outline" class="size-4" />
                            @else
                                <flux:icon name="calendar" variant="outline" class="size-4" />
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-[var(--theme-text)]">
                                {{ $date->label }}
                                @if ($isToday)
                                    <span class="ml-1 text-xs text-[var(--theme-accent)]">{{ __('Today!') }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-[var(--theme-text-muted)]">
                                {{ $date->date->translatedFormat('j F Y') }}
                                @if ($date->recurs_annually)
                                    <span class="ml-1">· {{ __('Yearly') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <flux:button size="xs" variant="subtle" icon="pencil" wire:click="openDateForm('{{ $date->id }}')" />
                        <flux:button size="xs" variant="subtle" icon="trash" wire:click="deleteDate('{{ $date->id }}')" wire:confirm="{{ __('Delete this date?') }}" wire:loading.attr="disabled" />
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-[var(--theme-text-muted)]">{{ __('No important dates yet.') }}</p>
            @endforelse
        </div>
    @endif

    {{-- Reminders Tab --}}
    @if ($tab === 'reminders')
        <div class="flex items-center justify-between">
            <p class="text-sm text-[var(--theme-text-muted)]">{{ __('Set reminders for things that matter') }}</p>
            <flux:button size="sm" icon="plus" wire:click="openReminderForm">{{ __('Add') }}</flux:button>
        </div>

        @if ($showReminderForm)
            <div class="rounded-lg border border-[var(--theme-accent)]/30 p-4"
                 style="background: color-mix(in srgb, var(--theme-bg) 95%, var(--theme-accent));">
                <div class="mb-3 text-sm font-medium text-[var(--theme-accent)]">
                    {{ $editingReminderId ? __('Edit Reminder') : __('New Reminder') }}
                </div>
                <div class="flex flex-col gap-3">
                    <input type="text" wire:model="reminderTitle" placeholder="{{ __('Reminder title') }}"
                           class="w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] placeholder-[var(--theme-text-muted)] focus:border-[var(--theme-accent)] focus:outline-none" />
                    @error('reminderTitle') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                    <textarea wire:model="reminderBody" placeholder="{{ __('Details (optional)') }}" rows="2"
                              class="w-full resize-none rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] placeholder-[var(--theme-text-muted)] focus:border-[var(--theme-accent)] focus:outline-none"></textarea>
                    <div>
                        <label class="mb-1 block text-xs text-[var(--theme-text-muted)]">{{ __('Type') }}</label>
                        <select wire:model="reminderType"
                                class="w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] focus:border-[var(--theme-accent)] focus:outline-none">
                            @foreach (\App\Enums\ReminderType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-[var(--theme-text-muted)]">{{ __('Remind at') }}</label>
                        <input type="datetime-local" wire:model="reminderAt"
                               class="w-full rounded-md border border-[var(--theme-border)] bg-[var(--theme-bg)] px-3 py-2 text-sm text-[var(--theme-text)] focus:border-[var(--theme-accent)] focus:outline-none" />
                        @error('reminderAt') <span class="text-xs text-[var(--theme-accent)]">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-2">
                        <flux:button size="xs" variant="subtle" wire:click="closeReminderForm">{{ __('Cancel') }}</flux:button>
                        <flux:button size="xs" variant="primary" wire:click="saveReminder" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveReminder">{{ __('Save') }}</span>
                            <span wire:loading wire:target="saveReminder">…</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-2">
            @forelse ($reminders as $reminder)
                @php
                    $isDue = $reminder->isDue();
                    $isPast = $reminder->remind_at->isPast();
                @endphp
                <div @class([
                    'flex items-center justify-between rounded-lg border p-3 transition-colors',
                    'border-[var(--theme-accent)] bg-[var(--theme-accent)]/5' => $isDue,
                    'border-[var(--theme-border)] opacity-50' => $reminder->is_completed,
                    'border-[var(--theme-border)]' => !$isDue && !$reminder->is_completed,
                ]) style="background: {{ ($isDue || $reminder->is_completed) ? '' : 'color-mix(in srgb, var(--theme-bg) 90%, transparent)' }};">
                    <div class="flex items-center gap-3">
                        <button wire:click="toggleComplete('{{ $reminder->id }}')"
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full transition-colors {{ $reminder->is_completed ? 'bg-green-500/20 text-green-500' : ($isDue ? 'bg-[var(--theme-accent)] text-white' : 'bg-[var(--theme-accent)]/10 text-[var(--theme-accent)]') }}">
                            @if ($reminder->is_completed)
                                <flux:icon name="check" variant="outline" class="size-4" />
                            @else
                                <flux:icon :name="$reminder->reminder_type?->icon() ?? 'bell'" variant="outline" class="size-4" />
                            @endif
                        </button>
                        <div>
                            <div @class(['text-sm font-medium', 'line-through text-[var(--theme-text-muted)]' => $reminder->is_completed, 'text-[var(--theme-text)]' => !$reminder->is_completed])>
                                {{ $reminder->title }}
                                @if ($reminder->reminder_type && $reminder->reminder_type !== \App\Enums\ReminderType::General)
                                    <span class="ml-1 rounded-full bg-[var(--theme-accent)]/10 px-1.5 py-0.5 text-[0.6rem] text-[var(--theme-accent)]">{{ $reminder->reminder_type->label() }}</span>
                                @endif
                                @if ($isDue)
                                    <span class="ml-1 text-xs text-[var(--theme-accent)]">{{ __('Due!') }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-[var(--theme-text-muted)]">
                                {{ $reminder->remind_at->translatedFormat('j F Y, H:i') }}
                                @if ($reminder->body)
                                    <span class="ml-1">· {{ str($reminder->body)->limit(50) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <flux:button size="xs" variant="subtle" icon="pencil" wire:click="openReminderForm('{{ $reminder->id }}')" />
                        <flux:button size="xs" variant="subtle" icon="trash" wire:click="deleteReminder('{{ $reminder->id }}')" wire:confirm="{{ __('Delete this reminder?') }}" wire:loading.attr="disabled" />
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-[var(--theme-text-muted)]">{{ __('No reminders yet.') }}</p>
            @endforelse
        </div>
    @endif
</div>
