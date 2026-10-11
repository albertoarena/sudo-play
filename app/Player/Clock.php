<?php

declare(strict_types=1);

namespace App\Player;

/**
 * Time for the autoplay player, which waits for Spotify to start.
 *
 * It is an interface so the tests can wait 20 seconds in no time at all.
 */
interface Clock
{
    /** Seconds from an arbitrary point. Only the difference between two readings means anything. */
    public function now(): float;

    public function sleep(float $seconds): void;
}
