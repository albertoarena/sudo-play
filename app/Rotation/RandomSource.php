<?php

declare(strict_types=1);

namespace App\Rotation;

/**
 * Where "surprise me" gets its randomness.
 *
 * Everything else in the rotation is deterministic. This is the one unknown,
 * so it is an interface: a test hands the picker a source that always answers
 * the same, and can then walk every option in turn.
 */
interface RandomSource
{
    /**
     * A number from 0 to $count - 1. The caller only asks when $count is 1 or more.
     */
    public function indexBelow(int $count): int;
}
