<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Player\Clock;

/**
 * A clock that only moves when something sleeps on it, or when a test moves
 * it. A test of a 20 second wait then takes no time at all.
 */
final class FakeClock implements Clock
{
    /** @var list<float> */
    public array $sleeps = [];

    public function __construct(private float $now = 0.0) {}

    public function now(): float
    {
        return $this->now;
    }

    public function sleep(float $seconds): void
    {
        $this->sleeps[] = $seconds;
        $this->advance($seconds);
    }

    public function advance(float $seconds): void
    {
        $this->now += $seconds;
    }
}
