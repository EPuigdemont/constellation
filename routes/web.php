<?php

use App\Http\Controllers\AvatarServeController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\GuestConvertController;
use App\Http\Controllers\GuestLocaleController;
use App\Http\Controllers\GuestLoginController;
use App\Http\Controllers\ImageServeController;
use App\Http\Controllers\ThemeController;
use App\Http\Middleware\RejectGuestSettings;
use App\Http\Middleware\RejectGuestUsers;
use App\Livewire\Actions\ManageFriends;
use App\Livewire\Calendar;
use App\Livewire\Constellation;
use App\Livewire\Desktop;
use App\Livewire\Diary;
use App\Livewire\ImagesGallery;
use App\Livewire\LoadingScreen;
use App\Livewire\Notes;
use App\Livewire\Notifications;
use App\Livewire\Reminders;
use App\Livewire\VisionBoard;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::redirect('/', '/login')->name('home');

Route::get('/about', fn (): View => view('pages.about'))->name('about');
Route::post('/locale', [GuestLocaleController::class, 'update'])->name('locale.guest.update');

// Guest mode routes
Route::get('/enter-as-guest', [GuestLoginController::class, 'show'])->name('guest.show');
Route::post('/enter-as-guest', [GuestLoginController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('guest.store');

Route::middleware(['auth'])->group(function () {
    // Guest conversion routes
    Route::get('/convert-guest', [GuestConvertController::class, 'show'])->name('guest.convert.show');
    Route::post('/convert-guest', [GuestConvertController::class, 'store'])->name('guest.convert.store');
    Route::get('loading', LoadingScreen::class)->name('loading');
    Route::get('welcome', Welcome::class)->name('welcome.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('canvas', Desktop::class)->name('canvas');
    Route::get('diary', Diary::class)->name('diary');
    Route::get('notes', Notes::class)->name('notes');
    Route::get('images', ImagesGallery::class)->name('images');
    Route::get('vision-board', VisionBoard::class)->name('vision-board');
    Route::get('calendar', Calendar::class)->name('calendar');
    Route::get('constellation', Constellation::class)->name('constellation');
    Route::get('reminders', Reminders::class)->name('reminders');
    Route::get('notifications', Notifications::class)->name('notifications');

    // Restricted for guest users
    Route::middleware(RejectGuestUsers::class)->group(function () {
        Route::get('friends', ManageFriends::class)->name('friends');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('images/{image}', ImageServeController::class)->name('images.serve');
    Route::get('avatar/{user}', AvatarServeController::class)->name('avatar.serve');
    Route::post('theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::get('data/export', DataExportController::class)
        ->middleware(RejectGuestSettings::class)
        ->name('data.export');
});

require __DIR__.'/settings.php';
