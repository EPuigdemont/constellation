<?php

use App\Livewire\Desktop;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Desktop::class)->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // All future entity routes will be registered here
});

require __DIR__.'/settings.php';
