<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConvertGuestUserRequest;
use App\Services\GuestAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuestConvertController extends Controller
{
    public function __construct(
        private readonly GuestAccountService $guestAccountService,
    ) {}

    /**
     * Show the guest conversion form.
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->isGuest()) {
            return redirect()->route('login');
        }

        return view('pages.auth.convert-guest', ['user' => $user]);
    }

    /**
     * Convert guest account to full user account.
     */
    public function store(ConvertGuestUserRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isGuest()) {
            return redirect()->route('login')->withErrors(['error' => 'Invalid request.']);
        }

        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();

            $userData = [
                'name' => (string) ($validated['name'] ?? ''),
                'username' => (string) ($validated['username'] ?? ''),
                'email' => (string) ($validated['email'] ?? ''),
                'password' => (string) ($validated['password'] ?? ''),
            ];

            $this->guestAccountService->convertGuestToFullUser($user, $userData);

            return redirect()->route('diary')->with('status', __('Your guest account has been successfully converted to a full account!'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Failed to convert guest account. Please try again.')]);
        }
    }
}
