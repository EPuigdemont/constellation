<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GuestAccountService;
use App\Services\TurnstileValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuestLoginController extends Controller
{
    public function __construct(
        private readonly GuestAccountService $guestAccountService,
        private readonly TurnstileValidationService $turnstileValidationService,
    ) {}

    /**
     * Show the guest login page (confirmation).
     */
    public function show(): View
    {
        return view('pages.auth.guest-login');
    }

    /**
     * Create a guest account and log in.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($this->turnstileValidationService->enabled()) {
            $token = $request->string('cf-turnstile-response')->toString();

            if ($token === '' || ! $this->turnstileValidationService->verify($token, $request->ip())) {
                return back()->withErrors([
                    'cf-turnstile-response' => __('We could not verify the security check. Please try again.'),
                ])->withInput();
            }
        }

        // Create guest user
        $user = $this->guestAccountService->createGuestUser();

        // Log in the guest user
        Auth::login($user);

        // Redirect to welcome or loading screen
        return redirect()->route('loading');
    }
}
