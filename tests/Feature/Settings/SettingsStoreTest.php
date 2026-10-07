<?php

declare(strict_types=1);

use App\Settings\DatabaseSettingsStore;
use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use Tests\Support\InMemorySettingsStore;

/**
 * One set of expectations, run against both implementations.
 *
 * The fake exists so the rest of the app can be tested without a database. It
 * is only worth having if it cannot drift from the real store, so every test
 * here runs twice.
 */
dataset('settings stores', [
    'database' => [DatabaseSettingsStore::class],
    'in memory' => [InMemorySettingsStore::class],
]);

/** @param  class-string<SettingsStore>  $store */
function settings(string $store): SettingsStore
{
    return app($store);
}

it('reads back what was written', function (string $store): void {
    $settings = settings($store);

    $settings->set(SettingKey::LastGenre->value, 'rock');

    expect($settings->get(SettingKey::LastGenre->value))->toBe('rock');
})->with('settings stores');

it('keeps the type a value was written with', function (string $store): void {
    $settings = settings($store);

    $settings->set(SettingKey::WeekdaysOnly->value, false);
    $settings->set(SettingKey::ReminderTime->value, '07:30');

    expect($settings->get(SettingKey::WeekdaysOnly->value))->toBeFalse()
        ->and($settings->get(SettingKey::ReminderTime->value))->toBe('07:30');
})->with('settings stores');

it('overwrites a value instead of keeping both', function (string $store): void {
    $settings = settings($store);

    $settings->set(SettingKey::LastMood->value, 'debugging');
    $settings->set(SettingKey::LastMood->value, 'shipped-it');

    expect($settings->get(SettingKey::LastMood->value))->toBe('shipped-it');
})->with('settings stores');

it('returns the default of a known setting that was never written', function (string $store): void {
    $settings = settings($store);

    expect($settings->get(SettingKey::PlayerMode->value))->toBe(PlayerMode::Autoplay->value)
        ->and($settings->get(SettingKey::ReminderTime->value))->toBe('09:00')
        ->and($settings->get(SettingKey::WeekdaysOnly->value))->toBeTrue()
        ->and($settings->get(SettingKey::ReminderEnabled->value))->toBeFalse()
        ->and($settings->get(SettingKey::LaunchAtLogin->value))->toBeFalse()
        ->and($settings->get(SettingKey::CatalogUrl->value))->toBe(SettingKey::DEFAULT_CATALOG_URL)
        ->and($settings->get(SettingKey::DefaultGenre->value))->toBeNull()
        ->and($settings->get(SettingKey::DefaultMood->value))->toBeNull();
})->with('settings stores');

it('returns the default of a known setting even when the caller offers another', function (string $store): void {
    expect(settings($store)->get(SettingKey::ReminderTime->value, '23:00'))->toBe('09:00');
})->with('settings stores');

it('returns the fallback for a key that is not a setting', function (string $store): void {
    $settings = settings($store);

    expect($settings->get('not_a_setting'))->toBeNull()
        ->and($settings->get('not_a_setting', 'whatever'))->toBe('whatever');
})->with('settings stores');

it('reads back a key that is not a setting once it has been written', function (string $store): void {
    $settings = settings($store);

    $settings->set('not_a_setting', 'written');

    expect($settings->get('not_a_setting', 'whatever'))->toBe('written');
})->with('settings stores');

it('goes back to the default when a setting is forgotten', function (string $store): void {
    $settings = settings($store);

    $settings->set(SettingKey::ReminderTime->value, '07:30');
    $settings->forget(SettingKey::ReminderTime->value);

    expect($settings->get(SettingKey::ReminderTime->value))->toBe('09:00');
})->with('settings stores');

it('forgets a key that was never written without complaining', function (string $store): void {
    settings($store)->forget(SettingKey::ReminderTime->value);
})->with('settings stores')->throwsNoExceptions();

it('answers with every default before anything is written', function (string $store): void {
    $settings = settings($store);

    foreach (SettingKey::cases() as $key) {
        expect($settings->get($key->value))->toBe($key->default());
    }
})->with('settings stores');

it('resolves the database store for the rest of the app', function (): void {
    expect(app(SettingsStore::class))->toBeInstanceOf(DatabaseSettingsStore::class);
});
