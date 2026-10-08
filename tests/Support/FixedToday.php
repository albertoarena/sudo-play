<?php

declare(strict_types=1);

namespace Tests\Support;

use App\MenuBar\Today;
use App\Rotation\CalendarDate;

/**
 * A day that only changes when the test says so.
 */
final class FixedToday implements Today
{
    private CalendarDate $date;

    public function __construct(string $date)
    {
        $this->date = CalendarDate::fromString($date);
    }

    public function moveTo(string $date): void
    {
        $this->date = CalendarDate::fromString($date);
    }

    public function today(): CalendarDate
    {
        return $this->date;
    }
}
