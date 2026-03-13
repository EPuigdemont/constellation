<?php

use App\Http\Controllers\AvatarServeController;
use App\Http\Controllers\ImageServeController;
use App\Http\Controllers\ThemeController;
use App\Livewire\Canvas;
use App\Livewire\DiaryView;
use App\Livewire\ImagesGallery;
use App\Livewire\LoadingScreen;
use App\Livewire\VisionBoard;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('loading', LoadingScreen::class)->name('loading');
    Route::get('welcome', Welcome::class)->name('welcome.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('canvas', Canvas::class)->name('canvas');
    Route::get('diary', DiaryView::class)->name('diary');
    Route::get('images', ImagesGallery::class)->name('images');
    Route::get('vision-board', VisionBoard::class)->name('vision-board');
});

Route::middleware(['auth'])->group(function () {
    Route::get('images/{image}', ImageServeController::class)->name('images.serve');
    Route::get('avatar/{user}', AvatarServeController::class)->name('avatar.serve');
    Route::post('theme', [ThemeController::class, 'update'])->name('theme.update');
});

require __DIR__.'/settings.php';
