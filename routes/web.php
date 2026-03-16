<?php

use App\Http\Controllers\AvatarServeController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\ImageServeController;
use App\Http\Controllers\ThemeController;
use App\Livewire\Calendar;
use App\Livewire\Canvas;
use App\Livewire\Constellation;
use App\Livewire\Reminders;
use App\Livewire\Diary;
use App\Livewire\ImagesGallery;
use App\Livewire\LoadingScreen;
use App\Livewire\Notifications;
use App\Livewire\VisionBoard;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('loading', LoadingScreen::class)->name('loading');
    Route::get('welcome', Welcome::class)->name('welcome.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('canvas', Canvas::class)->name('canvas');
    Route::get('diary', Diary::class)->name('diary');
    Route::get('images', ImagesGallery::class)->name('images');
    Route::get('vision-board', VisionBoard::class)->name('vision-board');
    Route::get('calendar', Calendar::class)->name('calendar');
    Route::get('constellation', Constellation::class)->name('constellation');
    Route::get('reminders', Reminders::class)->name('reminders');
    Route::get('notifications', Notifications::class)->name('notifications');
});

Route::middleware(['auth'])->group(function () {
    Route::get('images/{image}', ImageServeController::class)->name('images.serve');
    Route::get('avatar/{user}', AvatarServeController::class)->name('avatar.serve');
    Route::post('theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::get('data/export', DataExportController::class)->name('data.export');
});

require __DIR__.'/settings.php';
