<?php

declare(strict_types=1);

namespace App\Native;

use App\MenuBar\Today;
use App\Rotation\CalendarDate;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Today, in the time zone the Mac is set to.
 *
 * Laravel runs in UTC, and Electron does not pass the zone to PHP. macOS keeps
 * it as a link: `/etc/localtime` points into the zoneinfo folder, and the end
 * of that path is the zone name. A `TZ` variable, when one is set, wins, the
 * same as it does for every other program.
 *
 * When neither can be read the app zone is used. The pick is then a day off
 * for a few hours around midnight, which is better than no pick.
 */
final readonly class LocalToday implements Today
{
    public function __construct(
        private ?string $tz,
        private string $localtime = '/etc/localtime',
    ) {}

    public static function system(): self
    {
        $tz = getenv('TZ');

        return new self(is_string($tz) && $tz !== '' ? $tz : null);
    }

    public function today(): CalendarDate
    {
        return CalendarDate::fromDateTime($this->now());
    }

    public function now(): DateTimeImmutable
    {
        return Carbon::now($this->zone())->toDateTimeImmutable();
    }

    public function zone(): DateTimeZone
    {
        return $this->named($this->tz)
            ?? $this->named($this->fromLink())
            ?? new DateTimeZone(config()->string('app.timezone', 'UTC'));
    }

    /** The zone name at the end of the localtime link, such as `Europe/Rome`. */
    private function fromLink(): ?string
    {
        $target = @readlink($this->localtime);

        if ($target === false) {
            return null;
        }

        $marker = 'zoneinfo/';
        $at = strrpos($target, $marker);

        return $at === false ? null : substr($target, $at + strlen($marker));
    }

    private function named(?string $name): ?DateTimeZone
    {
        if ($name === null || $name === '') {
            return null;
        }

        try {
            return new DateTimeZone($name);
        } catch (Throwable) {
            return null;
        }
    }
}
