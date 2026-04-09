<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'john-doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('diary', absolute: false));

        $this->assertDatabaseHas('users', [
            'username' => 'john-doe',
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_unverified_user_is_redirected_to_email_verification_notice_when_accessing_verified_route(): void
    {
        $this->markTestSkipped('Email verification is disabled.');

        return;

        $this->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'john-doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('diary', absolute: false));

        $this->get(route('diary'))->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registration_requires_turnstile_when_enabled(): void
    {
        config()->set('services.turnstile.site_key', 'test-site-key');
        config()->set('services.turnstile.secret_key', 'test-secret-key');

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'john-doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register', absolute: false));
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_registration_accepts_valid_turnstile_token_when_enabled(): void
    {
        config()->set('services.turnstile.site_key', 'test-site-key');
        config()->set('services.turnstile.secret_key', 'test-secret-key');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'username' => 'john-doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cf-turnstile-response' => 'token',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('diary', absolute: false));
        Http::assertSentCount(1);
    }

    public function test_registration_is_rate_limited_after_too_many_attempts(): void
    {
        config()->set('auth.registration.max_attempts', 2);
        config()->set('auth.registration.decay_seconds', 3600);

        $this->post(route('register.store'), [
            'name' => 'John One',
            'username' => 'john-one',
            'email' => 'one@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->post(route('register.store'), [
            'name' => 'John Two',
            'username' => 'john-two',
            'email' => 'two@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'John Three',
            'username' => 'john-three',
            'email' => 'three@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register', absolute: false));
        $response->assertSessionHasErrors('email');
    }
}
