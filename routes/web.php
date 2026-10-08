<?php

declare(strict_types=1);

use App\Http\Controllers\MenuBarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MenuBarController::class, 'show'])->name('menubar');

Route::prefix('menu-bar')->name('menubar.')->controller(MenuBarController::class)->group(function (): void {
    Route::get('state', 'state')->name('state');
    Route::post('select', 'select')->name('select');
    Route::post('next', 'next')->name('next');
    Route::post('surprise', 'surprise')->name('surprise');
});
