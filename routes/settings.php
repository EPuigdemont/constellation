<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/data', 'pages::settings.data')
        ->middleware('App\\Http\\Middleware\\RejectGuestSettings')
        ->name('data.edit');

    $security = Route::livewire('settings/security', 'pages::settings.security')
        ->middleware('App\\Http\\Middleware\\RejectGuestSettings')
        ->name('security.edit');

    if (
        Features::canManageTwoFactorAuthentication()
        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
    ) {
        $security->middleware('password.confirm');
    }
});
