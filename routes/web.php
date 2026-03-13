<?php

use App\Http\Controllers\ImageServeController;
use App\Livewire\Desktop;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Desktop::class)->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('images/{image}', ImageServeController::class)->name('images.serve');
});

require __DIR__.'/settings.php';
