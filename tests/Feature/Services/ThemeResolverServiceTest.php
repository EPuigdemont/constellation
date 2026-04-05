<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\Theme;
use App\Models\User;
use App\Services\ThemeResolverService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_themes_default_to_on_and_use_seasonal_mapping(): void
    {
        $user = User::factory()->create([
            'theme' => Theme::Night->value,
            'automatic_themes' => true,
        ]);

        $resolved = app(ThemeResolverService::class)->resolveForUser(
            $user,
            CarbonImmutable::parse('2026-04-15 12:00:00')
        );

        $this->assertSame(Theme::Spring, $resolved);
    }

    public function test_special_dates_override_seasonal_mapping(): void
    {
        $user = User::factory()->create([
            'theme' => Theme::Summer->value,
            'automatic_themes' => true,
        ]);

        $service = app(ThemeResolverService::class);

        $valentines = $service->resolveForUser($user, CarbonImmutable::parse('2026-02-14 10:00:00'));
        $christmas = $service->resolveForUser($user, CarbonImmutable::parse('2026-12-25 10:00:00'));

        $this->assertSame(Theme::Love, $valentines);
        $this->assertSame(Theme::Cozy, $christmas);
    }

    public function test_manual_theme_is_used_when_automatic_themes_are_disabled(): void
    {
        $user = User::factory()->create([
            'theme' => Theme::Night->value,
            'automatic_themes' => false,
        ]);

        $resolved = app(ThemeResolverService::class)->resolveForUser(
            $user,
            CarbonImmutable::parse('2026-06-20 12:00:00')
        );

        $this->assertSame(Theme::Night, $resolved);
    }
}

