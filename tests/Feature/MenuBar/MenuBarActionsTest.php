<?php

declare(strict_types=1);

use App\MenuBar\Today;
use App\Rotation\RandomSource;
use App\Settings\SettingKey;
use Tests\Support\FixedRandomSource;
use Tests\Support\FixedToday;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;

// MenuBarSetup::with() and MenuBarSetup::expectedTitle() live in MenuBarStateTest.php.

describe('select', function (): void {
    it('updates the pick and remembers the choice', function (): void {
        $settings = MenuBarSetup::with(MenuBarCatalog::full());

        $this->postJson('/menu-bar/select', ['genre' => 'jazz', 'mood' => 'debugging'])
            ->assertOk()
            ->assertJsonPath('selection', ['genre' => 'jazz', 'mood' => 'debugging'])
            ->assertJsonPath('pick.id', 'jazz-debugging-01');

        expect($settings->get(SettingKey::LastGenre->value))->toBe('jazz')
            ->and($settings->get(SettingKey::LastMood->value))->toBe('debugging');

        $this->getJson('/menu-bar/state')->assertJsonPath('selection', ['genre' => 'jazz', 'mood' => 'debugging']);
    });

    it('starts Next again from today\'s pick', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full());

        $this->postJson('/menu-bar/select', ['genre' => 'rock', 'mood' => 'friday-deploy'])
            ->assertJsonPath('presses', 0)
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy'));
    });

    it('refuses a genre or mood the catalog does not have', function (array $body, string $field): void {
        $settings = MenuBarSetup::with(MenuBarCatalog::full());

        $this->postJson('/menu-bar/select', $body)->assertUnprocessable()->assertJsonValidationErrors($field);

        expect($settings->get(SettingKey::LastGenre->value))->toBeNull();
    })->with([
        'unknown genre' => [['genre' => 'polka', 'mood' => 'debugging'], 'genre'],
        'unknown mood' => [['genre' => 'rock', 'mood' => 'sleepy'], 'mood'],
        'missing genre' => [['mood' => 'debugging'], 'genre'],
    ]);
});

describe('next', function (): void {
    it('moves on to the next candidate', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy']);

        $this->postJson('/menu-bar/next', ['presses' => 0, 'date' => MenuBarSetup::TODAY])
            ->assertOk()
            ->assertJsonPath('presses', 1)
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy', 1));

        expect(MenuBarSetup::expectedTitle('rock', 'friday-deploy', 1))->not->toBe(MenuBarSetup::expectedTitle('rock', 'friday-deploy'));
    });

    it('wraps round to today\'s pick after the last candidate', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy']);

        $this->postJson('/menu-bar/next', ['presses' => 1, 'date' => MenuBarSetup::TODAY])
            ->assertJsonPath('presses', 2)
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy'));
    });

    it('starts again from today\'s pick when the count is from another day', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy']);

        $this->postJson('/menu-bar/next', ['presses' => 5, 'date' => '2026-10-07'])
            ->assertJsonPath('presses', 1)
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy', 1));
    });

    it('keeps the count when the window asks for the state again on the same day', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy']);

        $this->getJson('/menu-bar/state?presses=1&date='.MenuBarSetup::TODAY)
            ->assertJsonPath('presses', 1)
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy', 1));
    });

    it('drops the count when the day has changed since', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'rock', SettingKey::LastMood->value => 'friday-deploy']);
        app(Today::class)->moveTo('2026-10-09');

        $this->getJson('/menu-bar/state?presses=1&date='.MenuBarSetup::TODAY)
            ->assertJsonPath('date', '2026-10-09')
            ->assertJsonPath('presses', 0);
    });

    it('refuses a negative count', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full());

        $this->postJson('/menu-bar/next', ['presses' => -1, 'date' => MenuBarSetup::TODAY])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('presses');
    });
});

describe('surprise', function (): void {
    it('picks a pair that has a playlist, shows it and remembers it', function (): void {
        $settings = MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::LastGenre->value => 'lofi', SettingKey::LastMood->value => 'flow-state']);
        // The covered pairs, sorted: jazz|debugging, rock|friday-deploy.
        app()->instance(RandomSource::class, new FixedRandomSource(1));

        $this->postJson('/menu-bar/surprise')
            ->assertOk()
            ->assertJsonPath('selection', ['genre' => 'rock', 'mood' => 'friday-deploy'])
            ->assertJsonPath('pick.title', MenuBarSetup::expectedTitle('rock', 'friday-deploy'))
            ->assertJsonPath('presses', 0)
            ->assertJsonPath('note', null);

        expect($settings->get(SettingKey::LastGenre->value))->toBe('rock')
            ->and($settings->get(SettingKey::LastMood->value))->toBe('friday-deploy');
    });

    it('changes nothing when the catalog has no playlists', function (): void {
        $settings = MenuBarSetup::with(MenuBarCatalog::empty());

        $this->postJson('/menu-bar/surprise')
            ->assertOk()
            ->assertJsonPath('pick', null)
            ->assertJsonPath('actions.surprise', false);

        expect($settings->get(SettingKey::LastGenre->value))->toBeNull();
    });
});

it('uses the fixed day in these tests', function (): void {
    MenuBarSetup::with(MenuBarCatalog::full());

    expect(app(Today::class))->toBeInstanceOf(FixedToday::class);
});
