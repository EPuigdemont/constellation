<div data-card-type="diary_notebook"
     data-grid-item="widget"
     data-grid-key="diary_notebook"
     x-show="Alpine.store('desktop').showWidgets"
     x-data="diaryNotebook"
     x-on:dblclick="toggle()"
     :class="isOpen ? 'is-open' : ''"
     class="diary-notebook touch-none select-none"
     style="position: absolute; left: 100px; top: 100px; z-index: 1; width: 200px; min-height: 140px;">

    <div class="diary-notebook-closed">
        <svg class="size-8 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
        <span class="diary-notebook-closed-title">{{ __('Diary') }}</span>
        <span class="diary-notebook-closed-count" x-text="diaryEntries.length + ' {{ __('entries') }}'"></span>
    </div>

    <div class="diary-notebook-open">
        <div class="diary-notebook-spread" style="min-height: 200px;">
            <template x-if="currentEntries.length === 0">
                <div class="flex flex-1 items-center justify-center p-4 text-xs opacity-50">
                    {{ __('No diary entries yet') }}
                </div>
            </template>
            <template x-for="(entry, idx) in currentEntries" :key="idx">
                <div class="diary-notebook-page">
                    <div class="diary-notebook-page-date" x-text="formatDate(entry.created_at)"></div>
                    <div class="diary-notebook-page-title" x-text="entry.title || ''"></div>
                    <div class="diary-notebook-page-body" x-text="entry.preview || ''"></div>
                </div>
            </template>
        </div>
        <div class="diary-notebook-nav">
            <button x-on:click.stop="prevPage()" :disabled="currentPage <= 0">&laquo; {{ __('Prev') }}</button>
            <span x-text="(currentPage + 1) + ' / ' + totalPages"></span>
            <button x-on:click.stop="nextPage()" :disabled="currentPage >= totalPages - 1">{{ __('Next') }} &raquo;</button>
        </div>
    </div>
</div>

