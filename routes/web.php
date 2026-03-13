<?php

use App\Http\Controllers\ImageServeController;
use App\Livewire\Canvas;
use App\Livewire\DiaryView;
use App\Livewire\ImagesGallery;
use App\Livewire\VisionBoard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('canvas', Canvas::class)->name('canvas');
    Route::get('diary', DiaryView::class)->name('diary');
    Route::get('images', ImagesGallery::class)->name('images');
    Route::get('vision-board', VisionBoard::class)->name('vision-board');
});

Route::middleware(['auth'])->group(function () {
    Route::get('images/{image}', ImageServeController::class)->name('images.serve');
});

require __DIR__.'/settings.php';
