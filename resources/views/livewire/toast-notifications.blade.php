<div wire:poll.30s="poll">
    <div
        x-data="{
            toasts: [],
            addToast(toast) {
                const existing = this.toasts.find(t => t.id === toast.id);
                if (existing) return;
                this.toasts.push({ ...toast, visible: true });
                if (this.toasts.length > 3) {
                    this.toasts[0].visible = false;
                    const id = this.toasts[0].id;
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
                }
                setTimeout(() => this.dismiss(toast.id), 5000);
            },
            dismiss(id) {
                const t = this.toasts.find(t => t.id === id);
                if (t) t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
            },
            open(toast) {
                $wire.markRead(toast.id);
                this.dismiss(toast.id);
                window.location.href = toast.url;
            }
        }"
        @toast-show.window="addToast($event.detail)"
        class="fixed right-4 top-4 z-50 flex flex-col gap-2"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-[-8px]"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-[-8px]"
                class="flex w-80 items-start gap-3 rounded-lg border border-[var(--theme-border)] p-3 shadow-lg"
                style="background: color-mix(in srgb, var(--theme-bg) 97%, var(--theme-accent));"
            >
                <div class="mt-0.5 flex-shrink-0">
                    <template x-if="toast.type === 'friend_request'">
                        <flux:icon name="user-plus" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                    </template>
                    <template x-if="toast.type === 'item_shared'">
                        <flux:icon name="share" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                    </template>
                    <template x-if="toast.type === 'reminder_approaching'">
                        <flux:icon name="clock" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                    </template>
                    <template x-if="toast.type === 'reminder_due'">
                        <flux:icon name="bell-alert" variant="solid" class="size-4 text-[var(--theme-accent)]" />
                    </template>
                    <template x-if="!['friend_request','item_shared','reminder_approaching','reminder_due'].includes(toast.type)">
                        <flux:icon name="bell" variant="outline" class="size-4 text-[var(--theme-accent)]" />
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-[var(--theme-text)]" x-text="toast.message"></p>
                    <div class="mt-2 flex items-center gap-2">
                        <button
                            @click="open(toast)"
                            class="text-xs font-medium text-[var(--theme-accent)] hover:underline"
                        >{{ __('Open') }}</button>
                    </div>
                </div>
                <button
                    @click="dismiss(toast.id); $wire.markRead(toast.id)"
                    class="flex-shrink-0 text-[var(--theme-text-muted)] hover:text-[var(--theme-text)]"
                    aria-label="{{ __('Dismiss') }}"
                >
                    <flux:icon name="x-mark" variant="outline" class="size-4" />
                </button>
            </div>
        </template>
    </div>
</div>
