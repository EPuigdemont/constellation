<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Theme;
use App\Models\User;
use Carbon\CarbonInterface;

class ThemeResolverService
{
    /** @var array<string, string> */
    private const DEFAULT_SPECIAL_DATES = [
        '02-14' => Theme::Love->value,
        '10-31' => Theme::Night->value,
        '12-24' => Theme::Cozy->value,
        '12-25' => Theme::Cozy->value,
        '12-26' => Theme::Cozy->value,
    ];

    /** @var array<int, array{start: string, end: string, theme: string}> */
    private const DEFAULT_SEASONAL_RANGES = [
        ['start' => '03-01', 'end' => '05-31', 'theme' => Theme::Spring->value],
        ['start' => '06-01', 'end' => '08-31', 'theme' => Theme::Summer->value],
        ['start' => '09-01', 'end' => '11-30', 'theme' => Theme::Autumn->value],
        ['start' => '12-01', 'end' => '02-29', 'theme' => Theme::Winter->value],
    ];

    public function resolveForUser(User $user, ?CarbonInterface $now = null): Theme
    {
        $fallbackTheme = Theme::tryFrom((string) $user->theme) ?? Theme::Summer;

        if (! $user->automatic_themes) {
            return $fallbackTheme;
        }

        $currentDate = $now ?? now();
        $monthDay = $currentDate->format('m-d');

        $specialDateTheme = $this->resolveSpecialDateTheme($monthDay);
        if ($specialDateTheme !== null) {
            return $specialDateTheme;
        }

        $seasonalTheme = $this->resolveSeasonalTheme($monthDay);

        return $seasonalTheme ?? $fallbackTheme;
    }

    private function resolveSpecialDateTheme(string $monthDay): ?Theme
    {
        /** @var array<string, string> $specialDates */
        $specialDates = config('constellation.automatic_themes.special_dates', self::DEFAULT_SPECIAL_DATES);

        $themeValue = $specialDates[$monthDay] ?? null;
        if (! is_string($themeValue)) {
            return null;
        }

        return Theme::tryFrom($themeValue);
    }

    private function resolveSeasonalTheme(string $monthDay): ?Theme
    {
        /** @var array<int, array{start?: string, end?: string, theme?: string}> $ranges */
        $ranges = config('constellation.automatic_themes.seasonal_ranges', self::DEFAULT_SEASONAL_RANGES);

        foreach ($ranges as $range) {
            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;
            $themeValue = $range['theme'] ?? null;

            if (! is_string($start) || ! is_string($end) || ! is_string($themeValue)) {
                continue;
            }

            if (! $this->isMonthDayInRange($monthDay, $start, $end)) {
                continue;
            }

            return Theme::tryFrom($themeValue);
        }

        return null;
    }

    private function isMonthDayInRange(string $monthDay, string $start, string $end): bool
    {
        // MM-DD lexical comparison works for date ranges within the same format.
        if ($start <= $end) {
            return $monthDay >= $start && $monthDay <= $end;
        }

        // Handles year-wrapping ranges, e.g. 12-01..02-28.
        return $monthDay >= $start || $monthDay <= $end;
    }
}
