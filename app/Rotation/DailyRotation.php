<?php

declare(strict_types=1);

namespace App\Rotation;

/**
 * Where today lands in a sorted candidate list. Requirements 5.
 *
 * The list is walked in steps: each day moves on by the same number of places
 * and wraps at the end. Two numbers set the walk, and both come from the genre
 * and the mood, so every pair walks its own list in its own order:
 *
 * - the seed, where the walk starts
 * - the step, how far it moves each day
 *
 * The step is always coprime with the number of candidates. That gives two
 * things for free. Two days running can never land on the same place, because
 * the step is never a multiple of the count, so the promise that today differs
 * from yesterday holds by construction rather than by a tiebreak. And the walk
 * visits every candidate before it comes back round, so nothing is skipped.
 *
 * The result depends only on the day, the genre, the mood and how many
 * candidates there are. Nothing is stored and no clock is read, so two people
 * with the same catalog always see the same pick and can share it.
 */
final class DailyRotation
{
    /**
     * @param  int  $count  how many candidates there are, 1 or more
     */
    public static function indexFor(CalendarDate $date, string $genre, string $mood, int $count): int
    {
        if ($count < 2) {
            return 0;
        }

        $seed = crc32(self::key($genre, $mood)) % $count;
        $walked = $seed + $date->dayNumber() * self::stepFor($genre, $mood, $count);

        return self::wrap($walked, $count);
    }

    /**
     * How many places the pick moves each day: a number from 1 to $count - 1
     * that shares no factor with $count.
     *
     * The hash picks a starting step, then the walk moves on until it finds one
     * that is coprime. It always finds one, because 1 is coprime with
     * everything, and with two candidates 1 is the only choice, which makes
     * them alternate.
     *
     * @param  int  $count  how many candidates there are, 2 or more
     */
    public static function stepFor(string $genre, string $mood, int $count): int
    {
        $places = $count - 1;
        $step = 1 + crc32(self::key($genre, $mood).'|step') % $places;

        while (self::gcd($step, $count) !== 1) {
            $step = $step % $places + 1;
        }

        return $step;
    }

    private static function key(string $genre, string $mood): string
    {
        return $genre.'|'.$mood;
    }

    /**
     * The remainder, always from 0 to $count - 1. PHP's own `%` keeps the sign
     * of the left side, and a day before 1970 counts as a negative number.
     */
    private static function wrap(int $value, int $count): int
    {
        return ($value % $count + $count) % $count;
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }
}
