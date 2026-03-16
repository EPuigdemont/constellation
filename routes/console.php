<?php

use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:check', function () {
    $service = new ReminderService();

    User::all()->each(function (User $user) use ($service) {
        $notifications = $service->getTodayNotifications($user);
        if ($notifications->isNotEmpty()) {
            $this->info("User {$user->name}: {$notifications->count()} notification(s) today");
            foreach ($notifications as $n) {
                $this->line("  [{$n['type']}] {$n['title']}");
            }
        }
    });
})->purpose('Check for due reminders and important dates');

Schedule::command('reminders:check')->dailyAt('08:00');
