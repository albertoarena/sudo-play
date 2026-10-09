<?php

declare(strict_types=1);

namespace App\Settings;

/**
 * The settings the app keeps, and what each one is before the user has said
 * anything. Requirements 7.2.
 *
 * The store itself is a flat key to value interface, per requirements 11.1, so
 * it can later scope keys by profile without any caller changing. This enum is
 * what stops that flatness turning into magic strings: it names every key in
 * one place and owns its default.
 */
enum SettingKey: string
{
    /**
     * Where the catalog comes from. The `catalog-live` branch, never `main`,
     * because this URL is frozen into every binary. Requirements 12.2.
     */
    public const string DEFAULT_CATALOG_URL = 'https://raw.githubusercontent.com/albertoarena/sudo-play/catalog-live/catalog/playlists.yaml';

    public const string DEFAULT_REMINDER_TIME = '09:00';

    /** A reminder time: 24 hour `HH:MM`, from 00:00 to 23:59. */
    public const string REMINDER_TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /** The genre the window opens on when nothing has been used yet. */
    case DefaultGenre = 'default_genre';

    /** The mood the window opens on, and the one the daily reminder speaks for. */
    case DefaultMood = 'default_mood';

    case LastGenre = 'last_genre';

    case LastMood = 'last_mood';

    case PlayerMode = 'player_mode';

    case ReminderEnabled = 'reminder_enabled';

    /** 24 hour `HH:MM`, local time. */
    case ReminderTime = 'reminder_time';

    case WeekdaysOnly = 'weekdays_only';

    case LaunchAtLogin = 'launch_at_login';

    case CatalogUrl = 'catalog_url';

    /**
     * The local day, `YYYY-MM-DD`, the daily reminder was last shown. Not a
     * choice the user makes, so the settings window never shows it. It is what
     * lets a missed reminder be sent late, once. Requirements 7.3.
     */
    case ReminderSentOn = 'reminder_sent_on';

    /**
     * A genre or mood default of null means "not chosen yet". The window then
     * opens on the first genre and mood the catalog offers, so a fork with its
     * own catalog needs no code change and no id can be a default that the
     * catalog does not contain.
     */
    public function default(): string|bool|null
    {
        return match ($this) {
            self::DefaultGenre, self::DefaultMood, self::LastGenre, self::LastMood, self::ReminderSentOn => null,
            self::PlayerMode => PlayerMode::Autoplay->value,
            self::ReminderEnabled, self::LaunchAtLogin => false,
            self::ReminderTime => self::DEFAULT_REMINDER_TIME,
            self::WeekdaysOnly => true,
            self::CatalogUrl => self::DEFAULT_CATALOG_URL,
        };
    }

    /**
     * The default for a key, or the caller's fallback when the key is not one
     * of the app's settings.
     *
     * A known key always answers with its own default, even when the caller
     * offers another, so the default for a setting is stated once and here.
     */
    public static function defaultFor(string $key, mixed $fallback = null): mixed
    {
        $known = self::tryFrom($key);

        return $known === null ? $fallback : $known->default();
    }
}
