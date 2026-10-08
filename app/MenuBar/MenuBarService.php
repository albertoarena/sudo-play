<?php

declare(strict_types=1);

namespace App\MenuBar;

use App\Catalog\CatalogStore;
use App\Catalog\CatalogSync;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;
use App\Catalog\StoredCatalog;
use App\Rotation\DailyPicker;
use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;

/**
 * What the menu bar window shows, and what its buttons do. Requirements 7.1.
 *
 * The selection is the last genre and mood, kept in the settings store, so it
 * is remembered across restarts without the window holding any state of its
 * own. The Next position is the opposite: the window holds it and sends it
 * back, together with the day it was counted on. A restart starts the window
 * again at zero, and a new day makes the old count worthless, so both reset
 * the way requirements 5 asks.
 */
final readonly class MenuBarService
{
    public const string PERMISSION_HINT = 'The first time you press Play, macOS asks if Sudo Play may control Spotify.';

    public function __construct(
        private CatalogStore $catalogs,
        private CatalogSync $sync,
        private SettingsStore $settings,
        private DailyPicker $picker,
        private Today $today,
    ) {}

    /**
     * @param  int  $presses  how many times Next was pressed, as the window counted it
     * @param  string|null  $countedOn  the day the window counted them on
     */
    public function state(int $presses = 0, ?string $countedOn = null): MenuBarState
    {
        return $this->build($this->pressesToday($presses, $countedOn));
    }

    public function select(string $genre, string $mood): MenuBarState
    {
        $this->remember($genre, $mood);

        return $this->build(0);
    }

    public function next(int $presses, ?string $countedOn): MenuBarState
    {
        return $this->build($this->pressesToday($presses, $countedOn) + 1);
    }

    public function surprise(): MenuBarState
    {
        $catalog = $this->catalog()?->catalog;
        $pick = $catalog === null ? null : $this->picker->surprise($this->today->today(), $catalog->playlists);

        if ($pick !== null) {
            $this->remember($pick->genre, $pick->mood);
        }

        return $this->build(0);
    }

    public function playlist(string $id): ?Playlist
    {
        foreach ($this->catalog()->catalog->playlists ?? [] as $playlist) {
            if ($playlist->id === $id) {
                return $playlist;
            }
        }

        return null;
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

    private function build(int $presses): MenuBarState
    {
        $stored = $this->catalog();
        $catalog = $stored?->catalog;
        $date = $this->today->today();

        $genre = $this->choose(SettingKey::LastGenre, SettingKey::DefaultGenre, $this->genreIds());
        $mood = $this->choose(SettingKey::LastMood, SettingKey::DefaultMood, $this->moodIds());

        $pick = $catalog === null || $genre === null || $mood === null
            ? null
            : $this->picker->next($date, $genre, $mood, $catalog->playlists, $presses);

        return new MenuBarState(
            date: $date,
            catalog: $catalog,
            genre: $genre,
            mood: $mood,
            pick: $pick,
            presses: $pick === null ? 0 : $presses,
            syncedAt: $stored?->syncedAt,
            hint: $this->hint(),
        );
    }

    /**
     * The catalog the window draws from. A first run has nothing stored yet,
     * so the bundled copy goes in first. That reads one local file and never
     * waits on the network.
     */
    private function catalog(): ?StoredCatalog
    {
        $stored = $this->catalogs->current();

        if ($stored !== null) {
            return $stored;
        }

        $this->sync->loadBundledIfMissing();

        return $this->catalogs->current();
    }

    /**
     * The last choice if the catalog still has it, then the default, then the
     * first one the catalog offers.
     *
     * @param  list<string>  $known
     */
    private function choose(SettingKey $last, SettingKey $default, array $known): ?string
    {
        foreach ([$last, $default] as $key) {
            $value = $this->settings->get($key->value);

            if (is_string($value) && in_array($value, $known, true)) {
                return $value;
            }
        }

        return $known[0] ?? null;
    }

    private function remember(string $genre, string $mood): void
    {
        $this->settings->set(SettingKey::LastGenre->value, $genre);
        $this->settings->set(SettingKey::LastMood->value, $mood);
    }

    /** A count from another day, or from nowhere, starts again at today's pick. */
    private function pressesToday(int $presses, ?string $countedOn): int
    {
        if ($presses < 0 || $countedOn !== $this->today->today()->value) {
            return 0;
        }

        return $presses;
    }

    private function hint(): ?string
    {
        // The same reading as the player resolver: anything that is not a mode
        // means autoplay.
        $stored = $this->settings->get(SettingKey::PlayerMode->value);
        $mode = (is_string($stored) ? PlayerMode::tryFrom($stored) : null) ?? PlayerMode::Autoplay;

        return $mode === PlayerMode::Autoplay ? self::PERMISSION_HINT : null;
    }
}
