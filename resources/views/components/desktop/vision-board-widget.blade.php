<div data-card-type="vision_board_widget"
     data-grid-item="widget"
     data-grid-key="vision_board_widget"
     x-show="Alpine.store('desktop').showWidgets"
     x-data="{
         isOpen: false,
         images: [],
         loaded: false,
         async toggle() {
             this.isOpen = !this.isOpen;
             if (this.isOpen && !this.loaded) {
                 this.images = await $wire.getVisionBoardImages();
                 this.loaded = true;
             }

              if (Alpine.store('desktop').showGrid) {
                  window.dispatchEvent(new CustomEvent('desktop-grid-refresh'));
              }
         }
     }"
     x-on:dblclick="toggle()"
     class="vb-widget touch-none select-none"
     style="position: absolute; left: 350px; top: 100px; z-index: 1;">

    <template x-if="!isOpen">
        <div class="vb-widget-closed">
            <svg class="size-8 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span class="text-xs font-medium opacity-60">{{ __('Vision Board') }}</span>
            <span class="text-[0.6rem] opacity-40">{{ __('Double-click to open') }}</span>
        </div>
    </template>

    <template x-if="isOpen">
        <div class="vb-widget-open">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold opacity-70">{{ __('Vision Board') }}</span>
                <a href="{{ route('vision-board') }}" wire:navigate class="text-[0.6rem] text-[var(--theme-accent)] hover:underline">{{ __('Open Full') }} &rarr;</a>
            </div>
            <div class="vb-widget-grid">
                <template x-if="images.length === 0">
                    <div class="col-span-3 py-4 text-center text-xs opacity-50">{{ __('No images yet') }}</div>
                </template>
                <template x-for="img in images" :key="img.id">
                    <img :src="img.image_url" :alt="img.title" :title="img.title" class="h-16 w-full rounded object-cover" loading="lazy" />
                </template>
            </div>
        </div>
    </template>
</div>

