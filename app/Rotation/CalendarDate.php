<?php

declare(strict_types=1);

namespace App\Rotation;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Stringable;

/**
 * One calendar day, written `YYYY-MM-DD`.
 *
 * The rotation needs a day, not a moment. A `DateTimeImmutable` carries a time
 * and a zone as well, which invites a caller to hand the picker "now" in the
 * wrong zone and get yesterday's pick. This type carries the day only, and
 * `fromDateTime()` is the one place where a clock becomes a date: it reads the
 * day in the zone the moment already carries, which is the local day on the
 * user's Mac. Requirements 5.
 */
final readonly class CalendarDate implements Stringable
{
    private const string FORMAT = 'Y-m-d';

    private const int SECONDS_IN_A_DAY = 86400;

    private function __construct(
        public string $value,
    ) {}

    /**
     * @throws InvalidArgumentException when the date is not a real day written `YYYY-MM-DD`
     */
    public static function fromString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!'.self::FORMAT, $date);

        // createFromFormat accepts a lot, so compare the day it understood with
        // the text it was given. That rejects 2026-1-19 and 2026-02-30 alike.
        if ($parsed === false || $parsed->format(self::FORMAT) !== $date) {
            throw new InvalidArgumentException(sprintf('"%s" is not a calendar date written YYYY-MM-DD.', $date));
        }

        return new self($date);
    }

    public static function fromDateTime(DateTimeInterface $moment): self
    {
        return new self($moment->format(self::FORMAT));
    }

    /**
     * How many whole days lie between this day and the first of January 1970.
     *
     * The rotation moves one step per day, so it needs the day as a number.
     * The count is made in UTC, where every day is 24 hours long, so a clock
     * change never makes two calendar days share a number or skip one.
     */
    public function dayNumber(): int
    {
        $midnightUtc = DateTimeImmutable::createFromFormat(
            '!'.self::FORMAT,
            $this->value,
            new DateTimeZone('UTC'),
        );

        // The value was validated on the way in, so this cannot fail.
        assert($midnightUtc !== false);

        return intdiv($midnightUtc->getTimestamp(), self::SECONDS_IN_A_DAY);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
