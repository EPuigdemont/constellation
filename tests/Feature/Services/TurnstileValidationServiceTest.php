<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\TurnstileValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_returns_false_when_keys_not_configured(): void
    {
        config(['services.turnstile.site_key' => '', 'services.turnstile.secret_key' => '']);

        $service = new TurnstileValidationService;

        $this->assertFalse($service->enabled());
    }

    public function test_enabled_returns_true_when_both_keys_set(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => 'secret-xyz']);

        $service = new TurnstileValidationService;

        $this->assertTrue($service->enabled());
    }

    public function test_enabled_returns_false_when_only_site_key_set(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => '']);

        $service = new TurnstileValidationService;

        $this->assertFalse($service->enabled());
    }

    public function test_verify_returns_true_when_disabled(): void
    {
        config(['services.turnstile.site_key' => '', 'services.turnstile.secret_key' => '']);

        $service = new TurnstileValidationService;

        $this->assertTrue($service->verify('any-token'));
    }

    public function test_verify_returns_true_when_api_responds_success(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => 'secret-xyz']);

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $service = new TurnstileValidationService;

        $this->assertTrue($service->verify('valid-token', '1.2.3.4'));
    }

    public function test_verify_returns_false_when_api_responds_failure(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => 'secret-xyz']);

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);

        $service = new TurnstileValidationService;

        $this->assertFalse($service->verify('invalid-token'));
    }

    public function test_verify_returns_false_when_http_request_throws(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => 'secret-xyz']);

        Http::fake([
            'https://challenges.cloudflare.com/*' => function () {
                throw new \Exception('Connection refused');
            },
        ]);

        $service = new TurnstileValidationService;

        $this->assertFalse($service->verify('any-token'));
    }

    public function test_verify_returns_false_when_api_returns_non_200(): void
    {
        config(['services.turnstile.site_key' => 'site-abc', 'services.turnstile.secret_key' => 'secret-xyz']);

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response([], 503),
        ]);

        $service = new TurnstileValidationService;

        $this->assertFalse($service->verify('any-token'));
    }
}
