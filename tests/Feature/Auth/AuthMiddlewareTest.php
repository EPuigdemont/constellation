<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get(route('canvas'));

        $response->assertRedirect(route('login'));
    }

    public function test_settings_redirects_guests_to_login(): void
    {
        $response = $this->get('/settings/profile');

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_is_accessible_to_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_authenticated_users_visiting_login_are_redirected_to_diary(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('diary', absolute: false));
    }

    public function test_authenticated_users_visiting_root_do_not_enter_redirect_loop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get('/');

        $response->assertOk();
    }
}
