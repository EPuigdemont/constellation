<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_switch_to_english_from_login_screen(): void
    {
        $response = $this->from(route('login'))
            ->post(route('locale.guest.update'), ['locale' => 'en']);

        $response->assertRedirect(route('login', absolute: false));
        $this->assertSame('en', session(config('app.guest_locale_session_key', 'guest_locale')));

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Log in');
    }

    public function test_guests_can_switch_to_spanish_before_registering(): void
    {
        $response = $this->from(route('register'))
            ->post(route('locale.guest.update'), ['locale' => 'es']);

        $response->assertRedirect(route('register', absolute: false));

        $this->get(route('register'))
            ->assertOk()
            ->assertSeeText('Crear cuenta');
    }

    public function test_guest_locale_update_rejects_unsupported_language(): void
    {
        $response = $this->from(route('login'))
            ->post(route('locale.guest.update'), ['locale' => 'fr']);

        $response
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('locale');
    }
}
