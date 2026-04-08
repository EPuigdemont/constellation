<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_update_route_sets_theme_and_disables_automatic_themes(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create([
            'theme' => Theme::Summer->value,
            'automatic_themes' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('theme.update'), ['theme' => Theme::Night->value]);

        $response->assertOk()->assertJson(['ok' => true]);

        $freshUser = $user->fresh();

        $this->assertSame(Theme::Night->value, $freshUser?->theme);
        $this->assertFalse((bool) $freshUser?->automatic_themes);
    }
}
