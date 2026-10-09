<?php

declare(strict_types=1);

use App\Settings\PlayerMode;
use App\Settings\SettingKey;

it('covers every setting the app needs', function (): void {
    $keys = array_map(static fn (SettingKey $key): string => $key->value, SettingKey::cases());

    expect($keys)->toEqualCanonicalizing([
        'default_genre',
        'default_mood',
        'last_genre',
        'last_mood',
        'player_mode',
        'reminder_enabled',
        'reminder_time',
        'weekdays_only',
        'launch_at_login',
        'catalog_url',
        'reminder_sent_on',
    ]);
});

it('has no reminder sent before the first one', function (): void {
    expect(SettingKey::ReminderSentOn->default())->toBeNull();
});

it('defaults the player mode to autoplay', function (): void {
    expect(SettingKey::PlayerMode->default())->toBe(PlayerMode::Autoplay->value);
});

it('defaults the catalog url to the catalog-live branch over https', function (): void {
    expect(SettingKey::CatalogUrl->default())
        ->toBe('https://raw.githubusercontent.com/albertoarena/sudo-play/catalog-live/catalog/playlists.yaml')
        ->and(SettingKey::DEFAULT_CATALOG_URL)->toStartWith('https://')
        ->and(SettingKey::DEFAULT_CATALOG_URL)->toContain('catalog-live')
        ->and(SettingKey::DEFAULT_CATALOG_URL)->not->toContain('/main/');
});

it('gives the default of a known key', function (): void {
    expect(SettingKey::defaultFor('reminder_time'))->toBe('09:00');
});

it('prefers the default of a known key over the fallback offered', function (): void {
    expect(SettingKey::defaultFor('reminder_time', '23:00'))->toBe('09:00');
});

it('gives the fallback for a key that is not a setting', function (): void {
    expect(SettingKey::defaultFor('not_a_setting'))->toBeNull()
        ->and(SettingKey::defaultFor('not_a_setting', 'whatever'))->toBe('whatever');
});
