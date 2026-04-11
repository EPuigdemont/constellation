<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckGuestExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is a guest and their account has expired
        if ($user && $user->isGuest() && $user->isGuestExpired()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', __('Your guest session has expired. Please log in or enter as guest again.'));
        }

        return $next($request);
    }
}
