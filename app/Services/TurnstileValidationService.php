<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileValidationService
{
    public function enabled(): bool
    {
        return is_string(config('services.turnstile.site_key'))
            && config('services.turnstile.site_key') !== ''
            && is_string(config('services.turnstile.secret_key'))
            && config('services.turnstile.secret_key') !== '';
    }

    public function verify(string $token, ?string $remoteIp = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);
        } catch (Throwable) {
            return false;
        }

        return $response->ok() && $response->json('success') === true;
    }
}

