<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\Playlist;
use App\Settings\SettingKey;
use Inertia\Testing\AssertableInertia;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;

/*
 * The settings window. Requirements 7.2. It reads every value through the
 * settings store, and the genres and moods it offers come from the catalog.
 */

it('serves the settings page with the defaults on a first run', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    $this->get('/settings')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings')
            ->where('settings', [
                'defaultGenre' => null,
                'defaultMood' => null,
                'playerMode' => 'autoplay',
                'reminderEnabled' => false,
                'reminderTime' => '09:00',
                'weekdaysOnly' => true,
                'launchAtLogin' => false,
                'catalogUrl' => SettingKey::DEFAULT_CATALOG_URL,
            ]));
});

it('shows what the settings store holds', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [
        SettingKey::DefaultGenre->value => 'jazz',
        SettingKey::DefaultMood->value => 'debugging',
        SettingKey::PlayerMode->value => 'web',
        SettingKey::ReminderEnabled->value => true,
        SettingKey::ReminderTime->value => '17:30',
        SettingKey::WeekdaysOnly->value => false,
        SettingKey::LaunchAtLogin->value => true,
        SettingKey::CatalogUrl->value => 'https://example.com/fork.yaml',
    ]);

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('settings.defaultGenre', 'jazz')
        ->where('settings.defaultMood', 'debugging')
        ->where('settings.playerMode', 'web')
        ->where('settings.reminderEnabled', true)
        ->where('settings.reminderTime', '17:30')
        ->where('settings.weekdaysOnly', false)
        ->where('settings.launchAtLogin', true)
        ->where('settings.catalogUrl', 'https://example.com/fork.yaml'));
});

it('offers the genres and moods of the catalog, and the three player modes', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('options.genres', [
            ['id' => 'rock', 'label' => 'Rock'],
            ['id' => 'jazz', 'label' => 'Jazz'],
            ['id' => 'lofi', 'label' => 'Lo-fi'],
        ])
        ->where('options.moods.0', ['id' => 'friday-deploy', 'label' => 'Friday Deploy', 'emoji' => '🚀'])
        ->has('options.moods', 3)
        ->where('options.playerModes', [
            ['id' => 'autoplay', 'label' => 'Spotify app, autoplay'],
            ['id' => 'open-app', 'label' => 'Spotify app, open only'],
            ['id' => 'web', 'label' => 'Web player'],
        ]));
});

it('loads the bundled catalog on a first run, so there are genres to offer', function (): void {
    MenuBarSetup::with(null);

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('options.genres', fn ($genres): bool => count($genres) > 0));
});

it('shows a stored value the catalog no longer has as not chosen', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [
        SettingKey::DefaultGenre->value => 'polka',
        SettingKey::PlayerMode->value => 'gramophone',
    ]);

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('settings.defaultGenre', null)
        ->where('settings.playerMode', 'autoplay'));
});

it('shows the about panel and the catalog date', function (): void {
    config(['nativephp.version' => '1.2.3']);
    MenuBarSetup::with(MenuBarCatalog::full(), syncedAt: new DateTimeImmutable('2026-10-07 08:00:00'));

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('about.version', '1.2.3')
        ->where('about.repository', 'https://github.com/albertoarena/sudo-play')
        ->where('about.notice', 'Not affiliated with Spotify.')
        ->where('catalogDate', '2026-10-07'));
});

it('sums up privacy in one line in the about panel', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('about.privacy', 'No accounts, no analytics. The app only downloads the catalog.'));
});

it('credits each curator of the current catalog once, in alphabetical order', function (): void {
    $playlist = fn (string $id, ?string $curator): Playlist => new Playlist(
        id: $id,
        title: $id,
        spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
        genre: 'rock',
        moods: ['friday-deploy'],
        curator: $curator,
    );
    $catalog = MenuBarCatalog::full();

    MenuBarSetup::with(new Catalog(1, $catalog->genres, $catalog->moods, [
        $playlist('rock-01', 'zoe'),
        $playlist('rock-02', 'Bob'),
        $playlist('rock-03', null),
        $playlist('rock-04', 'zoe'),
        $playlist('rock-05', 'alice'),
    ]));

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('about.curators', ['alice', 'Bob', 'zoe']));
});

it('credits nobody when the catalog has no playlists', function (): void {
    MenuBarSetup::with(MenuBarCatalog::empty());

    $this->get('/settings')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('about.curators', []));
});
