<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\TurnstileValidationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private readonly TurnstileValidationService $turnstileValidationService,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $this->ensureIsNotRateLimited();

        $validator = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        $validator->after(function ($validator) use ($input): void {
            if (! $this->turnstileValidationService->enabled() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $token = $input['cf-turnstile-response'] ?? null;

            if (! is_string($token) || $token === '') {
                $validator->errors()->add('cf-turnstile-response', __('Please complete the security check.'));

                return;
            }

            if (! $this->turnstileValidationService->verify($token, request()->ip())) {
                $validator->errors()->add('cf-turnstile-response', __('We could not verify the security check. Please try again.'));
            }
        });

        $validator->validate();

        return User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }

    private function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('auth.registration.max_attempts', 5);
        $decaySeconds = (int) config('auth.registration.decay_seconds', 3600);
        $throttleKey = Str::transliterate('register|'.(string) request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => __('Too many registration attempts. Please try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                ]),
            ]);
        }

        RateLimiter::hit($throttleKey, $decaySeconds);
    }
}
