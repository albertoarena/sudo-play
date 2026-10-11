<?php

declare(strict_types=1);

use App\Settings\SettingKey;
use Inertia\Testing\AssertableInertia;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;

it('returns the genres, the moods, today\'s pick and the catalog date', function (): void {
    MenuBarSetup::with(
        MenuBarCatalog::full(),
        [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy'],
        new DateTimeImmutable('2026-10-07 21:15:00'),
    );

    $this->getJson('/menu-bar/state')
        ->assertOk()
        ->assertJsonPath('date', MenuBarSetup::TODAY)
        ->assertJsonPath('genres', [
            ['id' => 'rock', 'label' => 'Rock'],
            ['id' => 'jazz', 'label' => 'Jazz'],
            ['id' => 'lofi', 'label' => 'Lo-fi'],
        ])
        ->assertJsonPath('moods.0', [
            'id' => 'friday-deploy',
            'label' => 'Friday Deploy',
            'emoji' => '🚀',
            'description' => 'Brave, energetic, a bit risky.',
        ])
        ->assertJsonCount(3, 'moods')
        ->assertJsonPath('selection', ['genre' => 'rock', 'mood' => 'friday-deploy'])
        ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy'))
        ->assertJsonPath('pick.genre', ['id' => 'rock', 'label' => 'Rock'])
        ->assertJsonPath('pick.mood', ['id' => 'friday-deploy', 'label' => 'Friday Deploy', 'emoji' => '🚀'])
        ->assertJsonPath('presses', 0)
        ->assertJsonPath('note', null)
        ->assertJsonPath('empty', null)
        ->assertJsonPath('actions', ['play' => true, 'next' => true, 'surprise' => true])
        ->assertJsonPath('catalogDate', '2026-10-07');
});

it('gives the pick its id and curator, so the card and Play can use them', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'jazz', SettingKey::LastMood->value => 'debugging']);

    $this->getJson('/menu-bar/state')
        ->assertJsonPath('pick.id', 'jazz-debugging-01')
        ->assertJsonPath('pick.curator', null);
});

it('has no catalog date when the catalog was never downloaded', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    $this->getJson('/menu-bar/state')->assertJsonPath('catalogDate', null);
});

it('opens on the default genre and mood when nothing was used yet', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::DefaultGenre->value => 'jazz', SettingKey::DefaultMood->value => 'debugging']);

    $this->getJson('/menu-bar/state')->assertJsonPath('selection', ['genre' => 'jazz', 'mood' => 'debugging']);
});

it('opens on the first genre and mood when there is no default either', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    $this->getJson('/menu-bar/state')->assertJsonPath('selection', ['genre' => 'rock', 'mood' => 'friday-deploy']);
});

it('ignores a remembered genre or mood that the catalog no longer has', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [
        SettingKey::LastGenre->value => 'polka',
        SettingKey::LastMood->value => 'debugging',
        SettingKey::DefaultGenre->value => 'jazz',
    ]);

    $this->getJson('/menu-bar/state')->assertJsonPath('selection', ['genre' => 'jazz', 'mood' => 'debugging']);
});

it('explains a pick from the same mood in another genre', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'lofi', SettingKey::LastMood->value => 'debugging']);

    $this->getJson('/menu-bar/state')
        ->assertJsonPath('pick.id', 'jazz-debugging-01')
        ->assertJsonPath('pick.genre', ['id' => 'jazz', 'label' => 'Jazz'])
        ->assertJsonPath('note', 'no lofi for debugging, playing jazz');
});

it('explains a pick from the same genre in another mood', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'flow-state']);

    $this->getJson('/menu-bar/state')
        ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'flow-state'))
        ->assertJsonPath('pick.mood', ['id' => 'friday-deploy', 'label' => 'Friday Deploy', 'emoji' => '🚀'])
        ->assertJsonPath('note', 'no rock for flow-state, playing a rock pick for friday-deploy');
});

it('shows the empty state and disables every action when the catalog has no playlists', function (): void {
    MenuBarSetup::with(MenuBarCatalog::empty());

    $this->getJson('/menu-bar/state')
        ->assertOk()
        ->assertJsonCount(3, 'genres')
        ->assertJsonPath('pick', null)
        ->assertJsonPath('empty', 'No playlists in the catalog yet')
        ->assertJsonPath('note', null)
        ->assertJsonPath('actions', ['play' => false, 'next' => false, 'surprise' => false]);
});

it('keeps Surprise me when only the chosen pair has nothing at all', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'lofi', SettingKey::LastMood->value => 'flow-state']);

    $this->getJson('/menu-bar/state')
        ->assertJsonPath('pick', null)
        ->assertJsonPath('empty', 'No playlists for this genre or mood yet')
        ->assertJsonPath('actions', ['play' => false, 'next' => false, 'surprise' => true]);
});

it('adds a comment line to the command when the chosen pair has nothing at all', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'lofi', SettingKey::LastMood->value => 'flow-state']);

    $this->getJson('/menu-bar/state')
        ->assertJsonPath('pick', null)
        ->assertJsonPath('note', 'no playlists yet');
});

it('loads the bundled catalog when nothing is stored yet', function (): void {
    MenuBarSetup::with(null);

    $this->getJson('/menu-bar/state')
        ->assertOk()
        ->assertJsonCount(7, 'genres')
        ->assertJsonPath('empty', 'No playlists in the catalog yet');
});

it('explains the macOS permission prompt only in autoplay mode', function (string $mode, ?string $hint): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::PlayerMode->value => $mode]);

    $this->getJson('/menu-bar/state')->assertJsonPath('hint', $hint);
})->with([
    'autoplay' => ['autoplay', 'The first time you press Play, macOS asks if Sudo Play may control Spotify.'],
    'open app' => ['open-app', null],
    'web' => ['web', null],
]);

it('stops explaining the permission prompt once autoplay has worked', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [
        SettingKey::PlayerMode->value => 'autoplay',
        SettingKey::AutoplayWorked->value => true,
    ]);

    $this->getJson('/menu-bar/state')->assertJsonPath('hint', null);
});

it('renders the menu bar page with the same state', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'jazz', SettingKey::LastMood->value => 'debugging']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('MenuBar')
            ->where('state.selection', ['genre' => 'jazz', 'mood' => 'debugging'])
            ->where('state.pick.id', 'jazz-debugging-01'));
});
