<?php

declare(strict_types=1);

namespace Tests\Support;

use App\MenuBar\Today;
use App\Rotation\CalendarDate;
use DateTimeImmutable;
use DateTimeZone;

/**
 * A day and a time that only change when the test says so.
 *
 * The time is noon unless the test gives one, so a test about the date never
 * sits near midnight by accident.
 */
final class FixedToday implements Today
{
    private DateTimeImmutable $now;

    public function __construct(string $date, string $time = '12:00')
    {
        $this->moveTo($date, $time);
    }

    public function moveTo(string $date, string $time = '12:00'): void
    {
        $day = CalendarDate::fromString($date);

        $this->now = new DateTimeImmutable($day->value.' '.$time, new DateTimeZone('Europe/Rome'));
    }

    public function today(): CalendarDate
    {
        return CalendarDate::fromDateTime($this->now);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
