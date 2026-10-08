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
 * The scheduler asks once a minute. The answer is yes only in the chosen
 * minute, so a day gets one reminder and nothing is stored to remember that it
 * was sent. A Mac that sleeps through that minute gets no reminder that day,
 * which is better than a "today's pick" banner at four in the afternoon.
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

    private function isTime(DateTimeImmutable $now): bool
    {
        if ($this->settings->get(SettingKey::ReminderEnabled->value) !== true) {
            return false;
        }

        // 6 and 7 are Saturday and Sunday.
        if ($this->settings->get(SettingKey::WeekdaysOnly->value) !== false && (int) $now->format('N') >= 6) {
            return false;
        }

        return $now->format('H:i') === $this->time();
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
