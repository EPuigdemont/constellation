<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Services\ReminderService;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();
        $name = $user->name ?? '';

        session()->flash('status', __('Welcome back, :name!', ['name' => $name]));

        // Collect today's notifications for the banner
        $service = new ReminderService;
        $notifications = $service->getTodayNotifications($user);
        if ($notifications->isNotEmpty()) {
            session()->flash('today_notifications', $notifications->all());
        }

        // First login → welcome page
        if (! $user?->first_login_at) {
            return redirect()->route('welcome.show');
        }

        // Subsequent logins → loading screen
        return redirect()->route('loading');
    }
}
