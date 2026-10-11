<?php

declare(strict_types=1);

use App\Player\Clock;
use App\Player\SystemClock;

it('gives the rest of the app the system clock', function (): void {
    expect(app(Clock::class))->toBeInstanceOf(SystemClock::class);
});

it('moves forward by at least the time it slept', function (): void {
    $clock = new SystemClock;
    $before = $clock->now();

    $clock->sleep(0.01);

    expect($clock->now() - $before)->toBeGreaterThanOrEqual(0.01);
});
