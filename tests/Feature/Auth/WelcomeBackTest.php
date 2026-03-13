<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeBackTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_back_message_is_flashed_on_login(): void
    {
        $user = User::factory()->create(['name' => 'Enric']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHas('status', 'Welcome back, Enric!');
    }

    public function test_login_page_shows_constellation_branding(): void
    {
        $response = $this->get(route('login'));

        $response->assertSee('Welcome to Constellation');
        $response->assertSee('Sign in to continue');
        $response->assertDontSee('Don&#039;t have an account?');
    }
}
