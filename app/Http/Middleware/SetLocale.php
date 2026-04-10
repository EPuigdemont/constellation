<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['en']);
        $guestLocaleSessionKey = config('app.guest_locale_session_key', 'guest_locale');

        if ($request->user()?->language && in_array($request->user()->language, $supportedLocales, true)) {
            app()->setLocale($request->user()->language);
        } elseif ($request->session()->has($guestLocaleSessionKey)) {
            $guestLocale = (string) $request->session()->get($guestLocaleSessionKey);

            if (in_array($guestLocale, $supportedLocales, true)) {
                app()->setLocale($guestLocale);
            }
        }

        return $next($request);
    }
}
