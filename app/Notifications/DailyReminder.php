<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Rotation\CalendarDate;
use App\Rotation\DailyPicker;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use DateTimeImmutable;

/**
 * Whether it is time for the daily reminder, and what it says. Requirements 7.3.
 *
 * The scheduler asks once a minute. The answer is yes from the chosen minute
 * until CATCH_UP_HOURS later, on the same local day, and only while no
 * reminder was sent that day. So a Mac asleep at the chosen time still gets
 * the reminder when it wakes, a little late, and a day still gets one at most.
 *
 * The text names the plain daily pick for the default genre and mood. It never
 * follows Next, and it does not follow the last pair used in the window: the
 * default is the pair the user chose for this.
 *
 * Sending is somebody else's job, so this class has no NativePHP in it.
 */
final readonly class DailyReminder
{
    public const string TITLE = 'Sudo Play';

    /**
     * How late a missed reminder can still arrive. Long enough for a slow
     * start to the day, short enough that a morning habit is not announced
     * in the evening.
     */
    public const int CATCH_UP_HOURS = 4;

    public function __construct(
        private SettingsStore $settings,
        private DailyPicker $picker,
    ) {}

    /**
     * @param  DateTimeImmutable  $now  the moment in the Mac's own zone
     */
    public function due(DateTimeImmutable $now, ?Catalog $catalog): ?Reminder
    {
        if (! $this->isTime($now) || $catalog === null) {
            return null;
        }

        $genre = $this->chosen(SettingKey::DefaultGenre, array_map(fn (Genre $genre): string => $genre->id, $catalog->genres));
        $mood = $this->mood($catalog);

        if ($genre === null || $mood === null) {
            return null;
        }

        $pick = $this->picker->pick(CalendarDate::fromDateTime($now), $genre, $mood->id, $catalog->playlists);

        if ($pick === null) {
            return null;
        }

        $asked = $mood->emoji === '' ? $mood->label : $mood->emoji.' '.$mood->label;

        return new Reminder(self::TITLE, sprintf("%s? Today's pick is ready: %s", $asked, $pick->playlist->title));
    }

    /**
     * Records that today's reminder was shown, so no other one is sent today.
     *
     * @param  DateTimeImmutable  $now  the moment in the Mac's own zone
     */
    public function sent(DateTimeImmutable $now): void
    {
        $this->settings->set(SettingKey::ReminderSentOn->value, CalendarDate::fromDateTime($now)->value);
    }

    private function isTime(DateTimeImmutable $now): bool
    {
        if ($this->settings->get(SettingKey::ReminderEnabled->value) !== true) {
            return false;
        }

        // 6 and 7 are Saturday and Sunday.
        if ($this->settings->get(SettingKey::WeekdaysOnly->value) !== false && (int) $now->format('N') >= 6) {
            return false;
        }

        if ($this->settings->get(SettingKey::ReminderSentOn->value) === CalendarDate::fromDateTime($now)->value) {
            return false;
        }

        // Whole minutes, so all of the last minute in the window still counts.
        $minute = $now->setTime((int) $now->format('H'), (int) $now->format('i'));

        // The chosen time on the same day as $now. A late time cannot reach
        // past midnight: after midnight the chosen time is the new day's.
        [$hour, $minutes] = array_map(intval(...), explode(':', $this->time()));
        $chosen = $now->setTime($hour, $minutes);

        return $minute >= $chosen && $minute <= $chosen->modify(sprintf('+%d hours', self::CATCH_UP_HOURS));
    }

    /** The chosen time, or the default when what is stored is not `HH:MM`. */
    private function time(): string
    {
        $stored = $this->settings->get(SettingKey::ReminderTime->value);

        return is_string($stored) && preg_match(SettingKey::REMINDER_TIME_PATTERN, $stored) === 1
            ? $stored
            : SettingKey::DEFAULT_REMINDER_TIME;
    }

    private function mood(Catalog $catalog): ?Mood
    {
        $id = $this->chosen(SettingKey::DefaultMood, array_map(fn (Mood $mood): string => $mood->id, $catalog->moods));

        foreach ($catalog->moods as $mood) {
            if ($mood->id === $id) {
                return $mood;
            }
        }

        return null;
    }

    /**
     * The default when the catalog still has it, otherwise the first one the
     * catalog offers, the same way the window opens.
     *
     * @param  list<string>  $known
     */
    private function chosen(SettingKey $default, array $known): ?string
    {
        $value = $this->settings->get($default->value);

        return is_string($value) && in_array($value, $known, true) ? $value : ($known[0] ?? null);
    }
}
