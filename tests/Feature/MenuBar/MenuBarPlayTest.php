<?php

declare(strict_types=1);

use App\MenuBar\MenuBarWindow;
use App\MenuBar\Quitter;
use App\Player\PlayerInterface;
use App\Player\PlayResult;
use App\Player\UrlOpener;
use App\Settings\PlayerMode;
use Tests\Support\FakeMenuBarWindow;
use Tests\Support\FakeQuitter;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;
use Tests\Support\RecordingPlayer;
use Tests\Support\RecordingUrlOpener;

describe('play', function (): void {
    it('plays the playlist through the player', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full());
        $player = new RecordingPlayer;
        app()->instance(PlayerInterface::class, $player);

        $this->postJson('/menu-bar/play', ['playlist' => 'jazz-debugging-01'])
            ->assertOk()
            ->assertExactJson(['outcome' => 'played', 'message' => null, 'mode' => 'autoplay']);

        expect($player->played)->toBe(['jazz-debugging-01']);
    });

    it('passes on what the player has to say', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full());
        app()->instance(PlayerInterface::class, new RecordingPlayer(
            PlayResult::fellBack('Could not start Spotify. Opened the playlist in your browser instead.', 'osascript exited 1')
                ->ranAs(PlayerMode::Autoplay),
        ));

        $this->postJson('/menu-bar/play', ['playlist' => 'jazz-debugging-01'])
            ->assertOk()
            ->assertExactJson([
                'outcome' => 'fell-back',
                'message' => 'Could not start Spotify. Opened the playlist in your browser instead.',
                'mode' => 'autoplay',
            ]);
    });

    it('says which mode ran', function (PlayerMode $mode): void {
        MenuBarSetup::with(MenuBarCatalog::full());
        app()->instance(PlayerInterface::class, new RecordingPlayer(PlayResult::played()->ranAs($mode)));

        $this->postJson('/menu-bar/play', ['playlist' => 'jazz-debugging-01'])
            ->assertOk()
            ->assertExactJson(['outcome' => 'played', 'message' => null, 'mode' => $mode->value]);
    })->with([
        'autoplay' => [PlayerMode::Autoplay],
        'open app' => [PlayerMode::OpenApp],
        'web' => [PlayerMode::Web],
    ]);

    it('refuses a playlist the catalog does not have', function (array $body): void {
        MenuBarSetup::with(MenuBarCatalog::full());
        $player = new RecordingPlayer;
        app()->instance(PlayerInterface::class, $player);

        $this->postJson('/menu-bar/play', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('playlist');

        expect($player->played)->toBe([]);
    })->with([
        'unknown' => [['playlist' => 'polka-debugging-01']],
        'missing' => [[]],
    ]);
});

describe('footer', function (): void {
    it('opens the contributing page to add a playlist', function (): void {
        $opener = RecordingUrlOpener::working();
        app()->instance(UrlOpener::class, $opener);

        $this->postJson('/menu-bar/add-playlist')->assertNoContent();

        expect($opener->opened)->toBe(['https://github.com/albertoarena/sudo-play/blob/main/CONTRIBUTING.md']);
    });

    it('quits the app', function (): void {
        $quitter = new FakeQuitter;
        app()->instance(Quitter::class, $quitter);

        $this->postJson('/menu-bar/quit')->assertNoContent();

        expect($quitter->quits)->toBe(1);
    });
});

describe('keyboard', function (): void {
    it('hides the window for Esc', function (): void {
        $window = new FakeMenuBarWindow;
        app()->instance(MenuBarWindow::class, $window);

        $this->postJson('/menu-bar/hide')->assertNoContent();

        expect($window->hides)->toBe(1);
    });
});
