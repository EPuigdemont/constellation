<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_themes_toggle_can_be_updated(): void
    {
        $user = User::factory()->create([
            'automatic_themes' => true,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::settings.appearance')
            ->call('updateAutomaticThemes', false)
            ->assertSet('automaticThemes', false);

        $this->assertFalse((bool) $user->fresh()->automatic_themes);
    }

    public function test_manual_theme_selection_turns_off_automatic_themes(): void
    {
        $user = User::factory()->create([
            'theme' => Theme::Summer->value,
            'automatic_themes' => true,
        ]);

        $this->actingAs($user);

        Livewire::test('pages::settings.appearance')
            ->call('updateTheme', Theme::Night->value)
            ->assertSet('theme', Theme::Night->value)
            ->assertSet('automaticThemes', false);

        $freshUser = $user->fresh();

        $this->assertSame(Theme::Night->value, $freshUser?->theme);
        $this->assertFalse((bool) $freshUser?->automatic_themes);
    }
}

