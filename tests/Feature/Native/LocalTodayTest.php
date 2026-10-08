<?php

declare(strict_types=1);

use App\MenuBar\Today;
use App\Native\LocalToday;
use Illuminate\Support\Carbon;

/*
 * Laravel runs in UTC, but the daily pick follows the calendar on the user's
 * Mac. Late in the evening in New York it is already tomorrow in UTC, and the
 * user would see tomorrow's playlist. Requirements 5.
 */

function localtimeLink(string $zone): string
{
    $dir = sys_get_temp_dir().'/sudo-play-'.bin2hex(random_bytes(4));
    mkdir($dir.'/zoneinfo/'.dirname($zone), recursive: true);
    touch($dir.'/zoneinfo/'.$zone);
    symlink($dir.'/zoneinfo/'.$zone, $dir.'/localtime');

    return $dir.'/localtime';
}

afterEach(function (): void {
    Carbon::setTestNow();
});

it('reads the zone from the macOS localtime link', function (): void {
    $today = new LocalToday(tz: null, localtime: localtimeLink('America/New_York'));

    expect($today->zone()->getName())->toBe('America/New_York');
});

it('prefers the TZ variable when it is set', function (): void {
    $today = new LocalToday(tz: 'Europe/Rome', localtime: localtimeLink('America/New_York'));

    expect($today->zone()->getName())->toBe('Europe/Rome');
});

it('falls back to the app zone when the system zone cannot be read', function (?string $tz, string $localtime): void {
    $today = new LocalToday(tz: $tz, localtime: $localtime);

    expect($today->zone()->getName())->toBe('UTC');
})->with([
    'no link' => [null, '/nowhere/localtime'],
    'an unknown TZ and no link' => ['Mars/Olympus_Mons', '/nowhere/localtime'],
]);

it('gives the local calendar date, not the UTC one', function (): void {
    // 02:30 UTC on the 9th is still the evening of the 8th in New York.
    Carbon::setTestNow(Carbon::parse('2026-10-09 02:30:00', 'UTC'));

    $today = new LocalToday(tz: 'America/New_York', localtime: '/nowhere/localtime');

    expect($today->today()->value)->toBe('2026-10-08');
});

it('is what the app uses for today', function (): void {
    expect(app(Today::class))->toBeInstanceOf(LocalToday::class);
});
