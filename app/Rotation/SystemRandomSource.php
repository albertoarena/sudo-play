<?php

declare(strict_types=1);

namespace App\Rotation;

use Random\RandomException;

/**
 * The random source the app runs with.
 */
final class SystemRandomSource implements RandomSource
{
    /**
     * @throws RandomException when the system has no randomness to give
     */
    public function indexBelow(int $count): int
    {
        return random_int(0, $count - 1);
    }
}
