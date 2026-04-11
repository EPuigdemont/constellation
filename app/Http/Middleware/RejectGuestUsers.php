<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectGuestUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isGuest()) {
            return redirect()->route('canvas')
                ->with('error', __('This feature is not available for guest accounts.'));
        }

        return $next($request);
    }
}
