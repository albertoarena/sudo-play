<?php

declare(strict_types=1);

namespace App\SettingsWindow;

use App\Catalog\CatalogStore;
use App\Catalog\CatalogSync;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\StoredCatalog;
use App\Catalog\SyncOutcome;
use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * What the settings window shows, and what its buttons do. Requirements 7.2.
 *
 * Every value is read and written through the settings store, per requirements
 * 11.1. Launch at login is the one setting that also has to reach macOS, so it
 * is saved only once macOS has accepted it.
 */
final readonly class SettingsService
{
    public const string SAVED = 'Settings saved.';

    public const string LOGIN_ITEM_REFUSED = 'Settings saved, but launch at login could not be changed.';

    public const string CATALOG_UPDATED = 'Catalog updated.';

    public const string CATALOG_KEPT = 'Could not update the catalog. Keeping the one you have.';

    public function __construct(
        private SettingsStore $settings,
        private CatalogStore $catalogs,
        private CatalogSync $sync,
        private LoginItem $loginItem,
        private LoggerInterface $log,
    ) {}

    /**
     * The values, the choices the window offers, and the catalog date.
     *
     * @return array{settings: array<string, mixed>, options: array<string, list<array<string, string>>>, catalogDate: string|null}
     */
    public function state(): array
    {
        $catalog = $this->catalog()?->catalog;

        return [
            'settings' => $this->values()->toArray(),
            'options' => [
                'genres' => array_map(fn (Genre $genre): array => ['id' => $genre->id, 'label' => $genre->label], $catalog->genres ?? []),
                'moods' => array_map(fn (Mood $mood): array => ['id' => $mood->id, 'label' => $mood->label, 'emoji' => $mood->emoji], $catalog->moods ?? []),
                'playerModes' => array_map(fn (PlayerMode $mode): array => ['id' => $mode->value, 'label' => $mode->label()], PlayerMode::cases()),
            ],
            'catalogDate' => $this->catalogDate(),
        ];
    }

    /**
     * Saves values that were already validated, and says how it went.
     *
     * @return array{settings: array<string, mixed>, message: string}
     */
    public function save(SettingsValues $values): array
    {
        $this->settings->set(SettingKey::DefaultGenre->value, $values->defaultGenre);
        $this->settings->set(SettingKey::DefaultMood->value, $values->defaultMood);
        $this->settings->set(SettingKey::PlayerMode->value, $values->playerMode->value);
        $this->settings->set(SettingKey::ReminderEnabled->value, $values->reminderEnabled);
        $this->settings->set(SettingKey::ReminderTime->value, $values->reminderTime);
        $this->settings->set(SettingKey::WeekdaysOnly->value, $values->weekdaysOnly);
        $this->settings->set(SettingKey::CatalogUrl->value, $values->catalogUrl);

        $message = $this->applyLaunchAtLogin($values->launchAtLogin) ? self::SAVED : self::LOGIN_ITEM_REFUSED;

        return ['settings' => $this->values()->toArray(), 'message' => $message];
    }

    /**
     * "Update catalog now": the same sync the app runs every day, from the URL
     * that is saved, while the user waits. It gives up after 5 seconds.
     *
     * @return array{updated: bool, message: string, catalogDate: string|null}
     */
    public function updateCatalog(): array
    {
        $updated = $this->sync->sync()->outcome === SyncOutcome::Updated;

        return [
            'updated' => $updated,
            'message' => $updated ? self::CATALOG_UPDATED : self::CATALOG_KEPT,
            'catalogDate' => $this->catalogDate(),
        ];
    }

    /**
     * The people who curated the current catalog, for Credits in About. Each
     * handle once, in alphabetical order. Requirements 7.2.
     *
     * @return list<string>
     */
    public function curators(): array
    {
        $curators = [];

        foreach ($this->catalog()->catalog->playlists ?? [] as $playlist) {
            if ($playlist->curator !== null) {
                $curators[$playlist->curator] = true;
            }
        }

        $curators = array_keys($curators);
        usort($curators, strcasecmp(...));

        return $curators;
    }

    /** @return list<string> */
    public function genreIds(): array
    {
        return array_map(fn (Genre $genre): string => $genre->id, $this->catalog()->catalog->genres ?? []);
    }

    /** @return list<string> */
    public function moodIds(): array
    {
        return array_map(fn (Mood $mood): string => $mood->id, $this->catalog()->catalog->moods ?? []);
    }

    private function values(): SettingsValues
    {
        return SettingsValues::fromStore($this->settings, $this->genreIds(), $this->moodIds());
    }

    /**
     * Tells macOS first, and stores the choice only when that worked, so the
     * window never shows launch at login as on while macOS has it off.
     *
     * @return bool false when macOS could not be told
     */
    private function applyLaunchAtLogin(bool $open): bool
    {
        if ($this->settings->get(SettingKey::LaunchAtLogin->value) === $open) {
            return true;
        }

        try {
            $this->loginItem->set($open);
        } catch (Throwable $exception) {
            $this->log->warning('Could not change launch at login: '.$exception->getMessage());

            return false;
        }

        $this->settings->set(SettingKey::LaunchAtLogin->value, $open);

        return true;
    }

    /**
     * The catalog the window offers choices from. A first run has nothing
     * stored yet, so the bundled copy goes in first, as in the menu bar.
     */
    private function catalog(): ?StoredCatalog
    {
        if ($this->catalogs->current() === null) {
            $this->sync->loadBundledIfMissing();
        }

        return $this->catalogs->current();
    }

    private function catalogDate(): ?string
    {
        return $this->catalogs->current()?->syncedAt?->format('Y-m-d');
    }
}
