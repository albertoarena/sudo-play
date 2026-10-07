<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Rotation\CalendarDate;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Runs of consecutive days, for the tests that check a property day after day.
 */
final class CalendarDays
{
    /**
     * @return list<CalendarDate>
     */
    public static function run(string $from, int $days): array
    {
        $moment = new DateTimeImmutable($from.' 00:00:00', new DateTimeZone('UTC'));

        $run = [];

        for ($day = 0; $day < $days; $day++) {
            $run[] = CalendarDate::fromDateTime($moment);
            $moment = $moment->modify('+1 day');
        }

        return $run;
    }
}
