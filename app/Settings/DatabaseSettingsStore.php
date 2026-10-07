<?php

declare(strict_types=1);

namespace App\Settings;

use App\Models\Setting;
use JsonException;

/**
 * Settings in the SQLite database NativePHP keeps outside the app bundle, so
 * they survive an update.
 *
 * Values are stored as JSON. A text column would hand back the string "1" for
 * a checkbox, and every caller would have to remember to cast it.
 */
final class DatabaseSettingsStore implements SettingsStore
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::query()->find($key);

        if (! $row instanceof Setting) {
            return SettingKey::defaultFor($key, $default);
        }

        $stored = $row->getAttribute('value');

        if (! is_string($stored)) {
            return SettingKey::defaultFor($key, $default);
        }

        try {
            return json_decode($stored, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // Someone edited the file by hand. The default is a better answer
            // than a crash the user cannot do anything about.
            return SettingKey::defaultFor($key, $default);
        }
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)],
        );
    }

    public function forget(string $key): void
    {
        Setting::query()->whereKey($key)->delete();
    }
}
