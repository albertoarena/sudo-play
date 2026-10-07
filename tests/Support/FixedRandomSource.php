<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Rotation\RandomSource;

/**
 * A random source that is not random: it always answers with the same index,
 * and remembers how many choices it was offered. That makes "surprise me"
 * testable, because the test can walk every option in turn and check each one.
 */
final class FixedRandomSource implements RandomSource
{
    public ?int $lastCount = null;

    public function __construct(
        private readonly int $index = 0,
    ) {}

    public function indexBelow(int $count): int
    {
        $this->lastCount = $count;

        return $this->index;
    }
}
