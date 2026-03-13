<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $name = $request->user()?->name ?? '';

        session()->flash('status', __('Welcome back, :name!', ['name' => $name]));

        return redirect()->intended(config('fortify.home'));
    }
}
