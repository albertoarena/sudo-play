<?php

declare(strict_types=1);

use App\Http\Controllers\MenuBarController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MenuBarController::class, 'show'])->name('menubar');

Route::prefix('menu-bar')->name('menubar.')->controller(MenuBarController::class)->group(function (): void {
    Route::get('state', 'state')->name('state');
    Route::post('select', 'select')->name('select');
    Route::post('next', 'next')->name('next');
    Route::post('surprise', 'surprise')->name('surprise');
    Route::post('play', 'play')->name('play');
    Route::post('add-playlist', 'addPlaylist')->name('add-playlist');
    Route::post('quit', 'quit')->name('quit');
});

Route::get('settings', [SettingsController::class, 'show'])->name('settings');

Route::prefix('settings')->name('settings.')->controller(SettingsController::class)->group(function (): void {
    Route::post('/', 'save')->name('save');
    Route::post('update-catalog', 'updateCatalog')->name('update-catalog');
    Route::post('open-repository', 'openRepository')->name('open-repository');
});
