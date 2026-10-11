<?php

declare(strict_types=1);

namespace App\Player;

/** The real clock. It never goes backwards, even when the system time is changed. */
final class SystemClock implements Clock
{
    public function now(): float
    {
        return hrtime(true) / 1e9;
    }

    public function sleep(float $seconds): void
    {
        usleep((int) round($seconds * 1e6));
    }
}
