<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The catalog sync, once a day. Requirements 4.4.
 *
 * NativePHP runs `artisan schedule:run` every minute while the app is open, and
 * stops and restarts it around sleep, so this is all the wiring the daily sync
 * needs. The other half of the requirement, the sync just after startup, is
 * launched from NativeAppServiceProvider.
 *
 * withoutOverlapping, because the startup sync and this one can land at the same
 * minute on a machine that was launched at midnight.
 */
Schedule::command('catalog:sync')
    ->daily()
    ->withoutOverlapping();

/*
 * The daily reminder. Requirements 7.3.
 *
 * Asked every minute, because the time is a setting the user can change at
 * any moment. DailyReminder answers yes in the chosen minute only.
 */
Schedule::command('reminder:send')->everyMinute();
