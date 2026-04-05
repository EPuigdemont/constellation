<?php

use App\Services\DataImportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Data management')] class extends Component {
    use WithFileUploads;

    public $importFile = null;
    public string $importError = '';
    public bool $showImportConfirm = false;

    public function confirmImport(): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ]);

        $this->showImportConfirm = true;
    }

    public function cancelImport(): void
    {
        $this->showImportConfirm = false;
    }

    public function executeImport(): void
    {
        $this->importError = '';
        $this->showImportConfirm = false;

        try {
            $this->validate([
                'importFile' => ['required', 'file', 'mimes:zip', 'max:512000'],
            ]);

            $tempPath = $this->importFile->getRealPath();

            $service = app(DataImportService::class);
            $result = $service->import(Auth::user(), $tempPath);

            $this->importFile = null;

            session()->flash('import-success', __(
                'Import complete: :entities entities imported, :images images restored.',
                ['entities' => $result['entities'], 'images' => $result['images']]
            ));
        } catch (\Throwable $e) {
            $this->importError = __('Import failed: ') . $e->getMessage();
        }
    }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8 lg:space-y-10 lg:py-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Data')" :subheading="__('Export or import your Constellation data')">
        {{-- Export Section --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="sm">{{ __('Export Data') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Download all your entries, notes, images, reminders, tags, canvas positions, and settings as a ZIP file.') }}</flux:text>
            </div>

            <a href="{{ route('data.export') }}" class="inline-block">
                <flux:button icon="arrow-down-tray">
                    {{ __('Export All Data') }}
                </flux:button>
            </a>
        </div>

        <flux:separator class="my-6" />

        {{-- Import Section --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="sm">{{ __('Import Data') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Import data from a Constellation export file. This will add the exported content to your account — existing data will not be deleted.') }}</flux:text>
            </div>

            @if ($importError)
                <div class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                    {{ $importError }}
                </div>
            @endif

            @if (session('import-success'))
                <div class="rounded-md border border-green-300 bg-green-50 p-3 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                    {{ session('import-success') }}
                </div>
            @endif

            <div>
                <input type="file" wire:model="importFile" accept=".zip"
                       class="block w-full text-sm text-[var(--theme-text-muted)] file:mr-3 file:rounded-md file:border-0 file:bg-[var(--theme-accent)]/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[var(--theme-accent)] hover:file:bg-[var(--theme-accent)]/20" />
                @error('importFile') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror

                <div wire:loading wire:target="importFile" class="mt-2 text-sm text-[var(--theme-text-muted)]">
                    {{ __('Uploading file...') }}
                </div>
            </div>

            @if ($importFile && ! $showImportConfirm)
                <flux:button icon="arrow-up-tray" wire:click="confirmImport">
                    {{ __('Import Data') }}
                </flux:button>
            @endif

            @if ($showImportConfirm)
                <div class="rounded-md border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ __('Are you sure?') }}</p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ __('This will import all data from the export file into your account. Existing data will be preserved — nothing will be deleted or overwritten.') }}</p>
                    <div class="mt-3 flex gap-2">
                        <flux:button size="sm" variant="primary" wire:click="executeImport" wire:loading.attr="disabled" wire:target="executeImport">
                            <span wire:loading.remove wire:target="executeImport">{{ __('Yes, import') }}</span>
                            <span wire:loading wire:target="executeImport">{{ __('Importing...') }}</span>
                        </flux:button>
                        <flux:button size="sm" variant="subtle" wire:click="cancelImport">{{ __('Cancel') }}</flux:button>
                    </div>
                </div>
            @endif
        </div>
    </x-pages::settings.layout>
</section>
