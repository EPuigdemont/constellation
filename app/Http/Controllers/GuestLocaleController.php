<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SetGuestLocaleRequest;
use Illuminate\Http\RedirectResponse;

class GuestLocaleController extends Controller
{
    public function update(SetGuestLocaleRequest $request): RedirectResponse
    {
        $request->session()->put(
            config('app.guest_locale_session_key', 'guest_locale'),
            $request->string('locale')->value(),
        );

        return back();
    }
}
