<?php

declare(strict_types=1);

use App\Settings\SettingKey;
use App\SettingsWindow\LoginItem;
use Tests\Support\InMemorySettingsStore;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;
use Tests\Support\RecordingLoginItem;

/*
 * Saving the settings window. Requirements 7.2. Everything goes through the
 * settings store, and nothing is saved unless all of it is valid.
 */

/** @return array<string, mixed> */
function validSettings(array $overrides = []): array
{
    return [
        'defaultGenre' => 'jazz',
        'defaultMood' => 'debugging',
        'playerMode' => 'web',
        'reminderEnabled' => true,
        'reminderTime' => '17:30',
        'weekdaysOnly' => false,
        'launchAtLogin' => false,
        'catalogUrl' => 'https://example.com/fork.yaml',
        ...$overrides,
    ];
}

/** @param  array<string, mixed>  $settings */
function settingsWindow(array $settings = [], ?LoginItem $loginItem = null): InMemorySettingsStore
{
    $store = MenuBarSetup::with(MenuBarCatalog::full(), $settings);
    app()->instance(LoginItem::class, $loginItem ?? RecordingLoginItem::working());

    return $store;
}

it('saves every setting through the settings store', function (): void {
    $store = settingsWindow();

    $this->postJson('/settings', validSettings())
        ->assertOk()
        ->assertJsonPath('settings', validSettings())
        ->assertJsonPath('message', 'Settings saved.');

    expect($store->get(SettingKey::DefaultGenre->value))->toBe('jazz')
        ->and($store->get(SettingKey::DefaultMood->value))->toBe('debugging')
        ->and($store->get(SettingKey::PlayerMode->value))->toBe('web')
        ->and($store->get(SettingKey::ReminderEnabled->value))->toBeTrue()
        ->and($store->get(SettingKey::ReminderTime->value))->toBe('17:30')
        ->and($store->get(SettingKey::WeekdaysOnly->value))->toBeFalse()
        ->and($store->get(SettingKey::LaunchAtLogin->value))->toBeFalse()
        ->and($store->get(SettingKey::CatalogUrl->value))->toBe('https://example.com/fork.yaml');
});

it('lets the default genre and mood be not chosen', function (): void {
    $store = settingsWindow([SettingKey::DefaultGenre->value => 'jazz']);

    $this->postJson('/settings', validSettings(['defaultGenre' => null, 'defaultMood' => null]))->assertOk();

    expect($store->get(SettingKey::DefaultGenre->value))->toBeNull()
        ->and($store->get(SettingKey::DefaultMood->value))->toBeNull();
});

it('refuses what it cannot use, and saves nothing', function (string $field, mixed $value): void {
    $store = settingsWindow();

    $this->postJson('/settings', validSettings([$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect($store->get(SettingKey::PlayerMode->value))->toBe('autoplay')
        ->and($store->get(SettingKey::ReminderTime->value))->toBe('09:00');
})->with([
    'a time with no leading zero' => ['reminderTime', '9:00'],
    'an hour past 23' => ['reminderTime', '24:00'],
    'a minute past 59' => ['reminderTime', '09:60'],
    'a time in words' => ['reminderTime', 'soon'],
    'no time' => ['reminderTime', ''],
    'a genre the catalog does not have' => ['defaultGenre', 'polka'],
    'a mood the catalog does not have' => ['defaultMood', 'sleepy'],
    'an unknown player mode' => ['playerMode', 'gramophone'],
    'no player mode' => ['playerMode', null],
    'a plain http catalog URL' => ['catalogUrl', 'http://example.com/catalog.yaml'],
    'a catalog URL that is not a URL' => ['catalogUrl', 'catalog.yaml'],
    'another scheme' => ['catalogUrl', 'ftp://example.com/catalog.yaml'],
    'no catalog URL' => ['catalogUrl', ''],
    'a switch that is not on or off' => ['reminderEnabled', 'sometimes'],
    'a missing switch' => ['weekdaysOnly', null],
]);

it('explains a refused time and URL in plain words', function (): void {
    settingsWindow();

    $this->postJson('/settings', validSettings(['reminderTime' => '9am', 'catalogUrl' => 'http://example.com']))
        ->assertJsonValidationErrors([
            'reminderTime' => 'Use a 24 hour time, like 09:00.',
            'catalogUrl' => 'Use a link that starts with https://.',
        ]);
});

it('tells macOS when launch at login changes', function (): void {
    $loginItem = RecordingLoginItem::working();
    $store = settingsWindow(loginItem: $loginItem);

    $this->postJson('/settings', validSettings(['launchAtLogin' => true]))->assertOk();

    expect($loginItem->changes)->toBe([true])
        ->and($store->get(SettingKey::LaunchAtLogin->value))->toBeTrue();
});

it('leaves the login items alone when launch at login did not change', function (): void {
    $loginItem = RecordingLoginItem::working();
    settingsWindow([SettingKey::LaunchAtLogin->value => true], $loginItem);

    $this->postJson('/settings', validSettings(['launchAtLogin' => true]))->assertOk();

    expect($loginItem->changes)->toBe([]);
});

it('keeps the other settings and says so when macOS refuses the login item', function (): void {
    $store = settingsWindow(loginItem: RecordingLoginItem::failing());

    $this->postJson('/settings', validSettings(['launchAtLogin' => true]))
        ->assertOk()
        ->assertJsonPath('settings.launchAtLogin', false)
        ->assertJsonPath('message', 'Settings saved, but launch at login could not be changed.');

    expect($store->get(SettingKey::LaunchAtLogin->value))->toBeFalse()
        ->and($store->get(SettingKey::PlayerMode->value))->toBe('web');
});
