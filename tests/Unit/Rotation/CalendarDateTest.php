<?php

declare(strict_types=1);

use App\Rotation\CalendarDate;

it('keeps the date it was given', function (): void {
    expect(CalendarDate::fromString('2026-01-19')->value)->toBe('2026-01-19');
});

it('reads as the date in a string context', function (): void {
    expect((string) CalendarDate::fromString('2026-01-19'))->toBe('2026-01-19');
});

it('takes the calendar date of a moment, in that moment\'s own time zone', function (): void {
    $lateInRome = new DateTimeImmutable('2026-01-19 23:30:00', new DateTimeZone('Europe/Rome'));

    expect(CalendarDate::fromDateTime($lateInRome)->value)->toBe('2026-01-19');
});

it('takes the local date, not the UTC one', function (): void {
    // The same moment is already the 20th in Rome and still the 19th in New York.
    $moment = new DateTimeImmutable('2026-01-20 00:30:00', new DateTimeZone('Europe/Rome'));
    $sameMomentInNewYork = $moment->setTimezone(new DateTimeZone('America/New_York'));

    expect(CalendarDate::fromDateTime($moment)->value)->toBe('2026-01-20')
        ->and(CalendarDate::fromDateTime($sameMomentInNewYork)->value)->toBe('2026-01-19');
});

it('counts the days since the first of January 1970', function (string $date, int $expected): void {
    expect(CalendarDate::fromString($date)->dayNumber())->toBe($expected);
})->with([
    'the start' => ['1970-01-01', 0],
    'the day after the start' => ['1970-01-02', 1],
    'a day before the start' => ['1969-12-31', -1],
    'a known day' => ['2026-01-19', 20472],
]);

it('counts one day per calendar day, across months, years and leap days', function (string $date, string $next): void {
    $jump = CalendarDate::fromString($next)->dayNumber() - CalendarDate::fromString($date)->dayNumber();

    expect($jump)->toBe(1);
})->with([
    'end of a month' => ['2026-02-28', '2026-03-01'],
    'end of a year' => ['2026-12-31', '2027-01-01'],
    'a leap day' => ['2024-02-28', '2024-02-29'],
    'the day after a leap day' => ['2024-02-29', '2024-03-01'],
    'the spring clock change in Rome' => ['2026-03-29', '2026-03-30'],
    'the autumn clock change in Rome' => ['2026-10-25', '2026-10-26'],
]);

it('rejects a date that is not written as YYYY-MM-DD', function (string $date): void {
    expect(fn (): CalendarDate => CalendarDate::fromString($date))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'single digit month' => '2026-1-19',
    'single digit day' => '2026-01-9',
    'slashes' => '2026/01/19',
    'day first' => '19-01-2026',
    'with a time' => '2026-01-19 10:00:00',
    'empty' => '',
    'words' => 'today',
]);

it('rejects a date that does not exist', function (string $date): void {
    expect(fn (): CalendarDate => CalendarDate::fromString($date))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'thirtieth of February' => '2026-02-30',
    'leap day in a common year' => '2026-02-29',
    'thirteenth month' => '2026-13-01',
    'zero day' => '2026-01-00',
]);

it('says which date it refused', function (): void {
    expect(fn (): CalendarDate => CalendarDate::fromString('19-01-2026'))
        ->toThrow(InvalidArgumentException::class, '19-01-2026');
});

it('cannot be changed after it is built', function (): void {
    $date = CalendarDate::fromString('2026-01-19');

    expect(fn (): string => $date->value = '2026-01-20')->toThrow(Error::class);
});
