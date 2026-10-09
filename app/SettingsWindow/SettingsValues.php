<?php

declare(strict_types=1);

namespace App\SettingsWindow;

use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;

/**
 * Every value the settings window shows and saves. Requirements 7.2.
 *
 * `toArray()` is the shape `resources/js/types/settings.ts` describes. The two
 * have to change together.
 */
final readonly class SettingsValues
{
    public function __construct(
        public ?string $defaultGenre,
        public ?string $defaultMood,
        public PlayerMode $playerMode,
        public bool $reminderEnabled,
        public string $reminderTime,
        public bool $weekdaysOnly,
        public bool $launchAtLogin,
        public string $catalogUrl,
    ) {}

    /**
     * What the store holds, read the way the rest of the app reads it.
     *
     * A value the app could not use shows as its default: a genre or mood a
     * sync removed shows as not chosen, and anything that is not a player mode
     * shows as autoplay, which is what the player does with it anyway.
     *
     * @param  list<string>  $genres  the genre ids the catalog has
     * @param  list<string>  $moods  the mood ids the catalog has
     */
    public static function fromStore(SettingsStore $store, array $genres, array $moods): self
    {
        $time = self::text($store, SettingKey::ReminderTime);

        return new self(
            defaultGenre: self::oneOf(self::text($store, SettingKey::DefaultGenre), $genres),
            defaultMood: self::oneOf(self::text($store, SettingKey::DefaultMood), $moods),
            playerMode: PlayerMode::tryFrom((string) self::text($store, SettingKey::PlayerMode)) ?? PlayerMode::Autoplay,
            reminderEnabled: $store->get(SettingKey::ReminderEnabled->value) === true,
            reminderTime: $time !== null && preg_match(SettingKey::REMINDER_TIME_PATTERN, $time) === 1 ? $time : SettingKey::DEFAULT_REMINDER_TIME,
            weekdaysOnly: $store->get(SettingKey::WeekdaysOnly->value) !== false,
            launchAtLogin: $store->get(SettingKey::LaunchAtLogin->value) === true,
            catalogUrl: self::text($store, SettingKey::CatalogUrl) ?? SettingKey::DEFAULT_CATALOG_URL,
        );
    }

    private static function text(SettingsStore $store, SettingKey $key): ?string
    {
        $value = $store->get($key->value);

        return is_string($value) ? $value : null;
    }

    /** @param  list<string>  $ids */
    private static function oneOf(?string $id, array $ids): ?string
    {
        return in_array($id, $ids, true) ? $id : null;
    }

    /**
     * @return array{defaultGenre: string|null, defaultMood: string|null, playerMode: string, reminderEnabled: bool, reminderTime: string, weekdaysOnly: bool, launchAtLogin: bool, catalogUrl: string}
     */
    public function toArray(): array
    {
        return [
            'defaultGenre' => $this->defaultGenre,
            'defaultMood' => $this->defaultMood,
            'playerMode' => $this->playerMode->value,
            'reminderEnabled' => $this->reminderEnabled,
            'reminderTime' => $this->reminderTime,
            'weekdaysOnly' => $this->weekdaysOnly,
            'launchAtLogin' => $this->launchAtLogin,
            'catalogUrl' => $this->catalogUrl,
        ];
    }
}
