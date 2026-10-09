<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;
use App\Notifications\DailyReminder;
use App\Notifications\Reminder;
use App\Rotation\CalendarDate;
use App\Rotation\DailyPicker;
use App\Settings\SettingKey;
use Tests\Support\FixedRandomSource;
use Tests\Support\InMemorySettingsStore;
use Tests\Support\MenuBarCatalog;

/*
 * The daily reminder decides IF it is time to notify and WHAT to say.
 * Requirements 7.3. Sending it is somebody else's job, so all of this runs
 * without NativePHP.
 *
 * 2026-10-08 is a Thursday, 2026-10-10 a Saturday and 2026-10-11 a Sunday.
 */

/** @param  array<string, mixed>  $settings */
function reminderWith(array $settings = []): DailyReminder
{
    return new DailyReminder(
        new InMemorySettingsStore([SettingKey::ReminderEnabled->value => true, ...$settings]),
        new DailyPicker(new FixedRandomSource),
    );
}

function localMoment(string $moment): DateTimeImmutable
{
    return new DateTimeImmutable($moment, new DateTimeZone('Europe/Rome'));
}

function rockFridayDeploy(): array
{
    return [SettingKey::DefaultGenre->value => 'rock', SettingKey::DefaultMood->value => 'friday-deploy'];
}

/** The plain daily pick for a pair, which is what the reminder must name. */
function plainPickTitle(string $date, string $genre, string $mood, int $presses = 0): string
{
    $pick = (new DailyPicker(new FixedRandomSource))->next(
        CalendarDate::fromString($date),
        $genre,
        $mood,
        MenuBarCatalog::full()->playlists,
        $presses,
    );

    return (string) $pick?->playlist->title;
}

describe('when it fires', function (): void {
    it('fires at the chosen time', function (string $time, string $at): void {
        $reminder = reminderWith([SettingKey::ReminderTime->value => $time]);

        expect($reminder->due(localMoment($at), MenuBarCatalog::full()))->toBeInstanceOf(Reminder::class);
    })->with([
        'the default time' => ['09:00', '2026-10-08 09:00:00'],
        'later in that minute' => ['09:00', '2026-10-08 09:00:59'],
        'an afternoon time' => ['17:30', '2026-10-08 17:30:00'],
    ]);

    it('does not fire before the chosen time', function (string $at): void {
        expect(reminderWith()->due(localMoment($at), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'one minute before' => ['2026-10-08 08:59:59'],
        'just after midnight' => ['2026-10-08 00:00:00'],
    ]);

    // The scheduler asks every minute, so the day it was sent is what keeps a
    // day to one reminder.
    it('does not fire again once it was sent today', function (string $at): void {
        $reminder = reminderWith([SettingKey::ReminderSentOn->value => '2026-10-08']);

        expect($reminder->due(localMoment($at), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'the next minute' => ['2026-10-08 09:01:00'],
        'later in the window' => ['2026-10-08 11:00:00'],
        'the chosen minute again' => ['2026-10-08 09:00:00'],
    ]);

    it('fires again the next day', function (): void {
        $reminder = reminderWith([SettingKey::ReminderSentOn->value => '2026-10-07']);

        expect($reminder->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full()))->not->toBeNull();
    });

    // Today::now() hands over the moment in the Mac's zone. The same instant
    // in UTC is two hours earlier on the clock, so it is not the chosen minute.
    it('reads the clock in the zone the moment carries', function (): void {
        $rome = localMoment('2026-10-08 09:00:00');
        $utc = $rome->setTimezone(new DateTimeZone('UTC'));

        expect(reminderWith()->due($rome, MenuBarCatalog::full()))->not->toBeNull()
            ->and(reminderWith()->due($utc, MenuBarCatalog::full()))->toBeNull();
    });

    it('uses 09:00 when the stored time is not a time', function (mixed $stored): void {
        $reminder = reminderWith([SettingKey::ReminderTime->value => $stored]);

        expect($reminder->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full()))->not->toBeNull();
    })->with([
        'a word' => ['soon'],
        'no leading zero' => ['9:00'],
        'null' => [null],
    ]);
});

/*
 * A Mac asleep in the chosen minute still gets the reminder when it wakes, if
 * that is no more than DailyReminder::CATCH_UP_HOURS (4) later and still the
 * same local day.
 */
describe('a missed reminder', function (): void {
    it('fires late when the Mac wakes after the chosen time', function (): void {
        expect(reminderWith()->due(localMoment('2026-10-08 10:30:00'), MenuBarCatalog::full()))->toBeInstanceOf(Reminder::class);
    });

    it('fires up to four hours late', function (string $at): void {
        expect(reminderWith()->due(localMoment($at), MenuBarCatalog::full()))->not->toBeNull();
    })->with([
        'four hours late' => ['2026-10-08 13:00:00'],
        'later in that last minute' => ['2026-10-08 13:00:59'],
    ]);

    it('does not fire more than four hours late', function (string $at): void {
        expect(reminderWith()->due(localMoment($at), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'one minute too late' => ['2026-10-08 13:01:00'],
        'in the evening' => ['2026-10-08 22:00:00'],
    ]);

    it('fires late on the same day for a late chosen time', function (): void {
        $reminder = reminderWith([SettingKey::ReminderTime->value => '22:00']);

        expect($reminder->due(localMoment('2026-10-08 23:59:00'), MenuBarCatalog::full()))->not->toBeNull();
    });

    // 01:00 is inside four hours of 22:00, but it is the next day. Yesterday's
    // reminder must not arrive in the night.
    it('does not carry a late chosen time past midnight', function (string $at): void {
        $reminder = reminderWith([SettingKey::ReminderTime->value => '22:00', SettingKey::WeekdaysOnly->value => false]);

        expect($reminder->due(localMoment($at), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'just after midnight' => ['2026-10-09 00:00:00'],
        'at one in the morning' => ['2026-10-09 01:00:00'],
    ]);

    it('names the pick of the day it is sent', function (): void {
        $reminder = reminderWith(rockFridayDeploy())->due(localMoment('2026-10-08 12:00:00'), MenuBarCatalog::full());

        expect($reminder?->body)->toBe("🚀 Friday Deploy? Today's pick is ready: ".plainPickTitle('2026-10-08', 'rock', 'friday-deploy'));
    });

    it('does not fire late at the weekend when weekdays only is on', function (): void {
        expect(reminderWith()->due(localMoment('2026-10-10 10:30:00'), MenuBarCatalog::full()))->toBeNull();
    });

    it('does not fire late when the reminder is off', function (): void {
        $reminder = reminderWith([SettingKey::ReminderEnabled->value => false]);

        expect($reminder->due(localMoment('2026-10-08 10:30:00'), MenuBarCatalog::full()))->toBeNull();
    });

    it('does not fire late when there is nothing to pick', function (): void {
        expect(reminderWith()->due(localMoment('2026-10-08 10:30:00'), MenuBarCatalog::empty()))->toBeNull();
    });
});

describe('once it is sent', function (): void {
    it('remembers the day', function (): void {
        $settings = new InMemorySettingsStore([SettingKey::ReminderEnabled->value => true]);
        $reminder = new DailyReminder($settings, new DailyPicker(new FixedRandomSource));

        $reminder->sent(localMoment('2026-10-08 09:00:00'));

        expect($settings->get(SettingKey::ReminderSentOn->value))->toBe('2026-10-08')
            ->and($reminder->due(localMoment('2026-10-08 09:01:00'), MenuBarCatalog::full()))->toBeNull();
    });

    // 00:30 in Rome is still the day before in UTC.
    it('remembers the local day, not the UTC one', function (): void {
        $settings = new InMemorySettingsStore;
        $reminder = new DailyReminder($settings, new DailyPicker(new FixedRandomSource));

        $reminder->sent(localMoment('2026-10-08 00:30:00'));

        expect($settings->get(SettingKey::ReminderSentOn->value))->toBe('2026-10-08');
    });
});

describe('weekdays only', function (): void {
    it('skips Saturday and Sunday', function (string $day): void {
        $reminder = reminderWith([SettingKey::WeekdaysOnly->value => true]);

        expect($reminder->due(localMoment($day.' 09:00:00'), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'Saturday' => ['2026-10-10'],
        'Sunday' => ['2026-10-11'],
    ]);

    it('still fires on a weekday', function (string $day): void {
        $reminder = reminderWith([SettingKey::WeekdaysOnly->value => true]);

        expect($reminder->due(localMoment($day.' 09:00:00'), MenuBarCatalog::full()))->not->toBeNull();
    })->with([
        'Monday' => ['2026-10-12'],
        'Thursday' => ['2026-10-08'],
    ]);

    it('fires at the weekend when weekdays only is off', function (): void {
        $reminder = reminderWith([SettingKey::WeekdaysOnly->value => false]);

        expect($reminder->due(localMoment('2026-10-10 09:00:00'), MenuBarCatalog::full()))->not->toBeNull();
    });

    it('is on unless the user turns it off', function (): void {
        expect(reminderWith()->due(localMoment('2026-10-11 09:00:00'), MenuBarCatalog::full()))->toBeNull();
    });
});

describe('when it is off', function (): void {
    it('never fires', function (string $at): void {
        $reminder = reminderWith([SettingKey::ReminderEnabled->value => false, SettingKey::WeekdaysOnly->value => false]);

        expect($reminder->due(localMoment($at), MenuBarCatalog::full()))->toBeNull();
    })->with([
        'at the chosen time' => ['2026-10-08 09:00:00'],
        'on a Saturday' => ['2026-10-10 09:00:00'],
        'at any other time' => ['2026-10-08 18:45:00'],
    ]);

    it('is off until the user turns it on', function (): void {
        $reminder = new DailyReminder(new InMemorySettingsStore, new DailyPicker(new FixedRandomSource));

        expect($reminder->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full()))->toBeNull();
    });
});

describe('what it says', function (): void {
    it('uses the wording from requirements 7.3', function (): void {
        $catalog = new Catalog(
            version: 1,
            genres: [new Genre('jazz', 'Jazz')],
            moods: [new Mood('coffee-needed', 'Coffee Needed', '☕')],
            playlists: [new Playlist('jazz-coffee-needed-01', 'Slow jazz, strong coffee', 'aB1cD2eF3gH4iJ5kL6mN7o', 'jazz', ['coffee-needed'])],
        );

        $reminder = reminderWith([SettingKey::DefaultGenre->value => 'jazz', SettingKey::DefaultMood->value => 'coffee-needed'])
            ->due(localMoment('2026-10-08 09:00:00'), $catalog);

        expect($reminder?->title)->toBe('Sudo Play')
            ->and($reminder?->body)->toBe("☕ Coffee Needed? Today's pick is ready: Slow jazz, strong coffee");
    });

    it('names today\'s plain daily pick for the default genre and mood, never a Next result', function (): void {
        $reminder = reminderWith(rockFridayDeploy())->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full());

        $plain = plainPickTitle('2026-10-08', 'rock', 'friday-deploy');

        expect($reminder?->body)->toBe("🚀 Friday Deploy? Today's pick is ready: {$plain}")
            ->and($plain)->not->toBe(plainPickTitle('2026-10-08', 'rock', 'friday-deploy', presses: 1));
    });

    it('ignores the last genre and mood used in the window', function (): void {
        $reminder = reminderWith([
            ...rockFridayDeploy(),
            SettingKey::LastGenre->value => 'jazz',
            SettingKey::LastMood->value => 'debugging',
        ])->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full());

        expect($reminder?->body)->toStartWith('🚀 Friday Deploy?');
    });

    it('follows the day', function (): void {
        $reminder = reminderWith(rockFridayDeploy());

        $thursday = $reminder->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full());
        $friday = $reminder->due(localMoment('2026-10-09 09:00:00'), MenuBarCatalog::full());

        expect($thursday?->body)->toEndWith(plainPickTitle('2026-10-08', 'rock', 'friday-deploy'))
            ->and($friday?->body)->toEndWith(plainPickTitle('2026-10-09', 'rock', 'friday-deploy'))
            ->and($thursday?->body)->not->toBe($friday?->body);
    });

    it('uses the first genre and mood of the catalog when no default is chosen', function (array $settings): void {
        $reminder = reminderWith($settings)->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full());

        // rock and friday-deploy come first in MenuBarCatalog.
        expect($reminder?->body)->toBe("🚀 Friday Deploy? Today's pick is ready: ".plainPickTitle('2026-10-08', 'rock', 'friday-deploy'));
    })->with([
        'nothing chosen' => [[]],
        'a default a sync removed' => [[SettingKey::DefaultGenre->value => 'polka', SettingKey::DefaultMood->value => 'sleepy']],
    ]);

    it('leaves out the emoji when the mood has none', function (): void {
        $catalog = new Catalog(
            version: 1,
            genres: [new Genre('rock', 'Rock')],
            moods: [new Mood('debugging', 'Debugging')],
            playlists: [new Playlist('rock-debugging-01', 'Patient riffs', 'aB1cD2eF3gH4iJ5kL6mN7o', 'rock', ['debugging'])],
        );

        $reminder = reminderWith()->due(localMoment('2026-10-08 09:00:00'), $catalog);

        expect($reminder?->body)->toBe("Debugging? Today's pick is ready: Patient riffs");
    });

    it('names a fallback pick when the pair has no playlist of its own', function (): void {
        // No lofi playlist, so the same mood in another genre: the jazz one.
        $reminder = reminderWith([SettingKey::DefaultGenre->value => 'lofi', SettingKey::DefaultMood->value => 'debugging'])
            ->due(localMoment('2026-10-08 09:00:00'), MenuBarCatalog::full());

        expect($reminder?->body)->toBe("🐛 Debugging? Today's pick is ready: Slow jazz for long stack traces");
    });

    it('stays quiet when there is nothing to pick', function (?Catalog $catalog): void {
        expect(reminderWith()->due(localMoment('2026-10-08 09:00:00'), $catalog))->toBeNull();
    })->with([
        'no playlists' => [MenuBarCatalog::empty()],
        'no catalog' => [null],
    ]);
});
