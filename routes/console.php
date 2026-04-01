<?php

use App\Models\User;
use App\Services\ReminderService;
use App\Services\UnverifiedUserCleanupService;
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

Artisan::command('users:purge-unverified {--hours=336}', function (UnverifiedUserCleanupService $cleanupService) {
    $hours = max(1, (int) $this->option('hours'));
    $deleted = $cleanupService->purgeOlderThanHours($hours);

    $this->info("Deleted {$deleted} unverified user(s) older than {$hours} hour(s).");
})->purpose('Delete stale unverified users');

Schedule::command('reminders:check')->dailyAt('08:00');
Schedule::command('users:purge-unverified --hours=72')->dailyAt('03:00');
