@php
    $currentLocale = app()->getLocale();
    $supportedLocales = config('app.supported_locales', ['en', 'es']);

    $labels = [
        'en' => __('English'),
        'es' => __('Spanish'),
    ];
@endphp

<div class="flex items-center justify-center">
    <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900">
        @foreach ($supportedLocales as $locale)
            <form method="POST" action="{{ route('locale.guest.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}" />
                <button
                    type="submit"
                    @class([
                        'rounded-md px-3 py-1 text-xs font-medium transition-colors',
                        'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $currentLocale === $locale,
                        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => $currentLocale !== $locale,
                    ])
                    aria-current="{{ $currentLocale === $locale ? 'true' : 'false' }}"
                >
                    {{ $labels[$locale] ?? strtoupper($locale) }}
                </button>
            </form>
        @endforeach
    </div>
</div>

