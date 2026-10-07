<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Settings\SettingKey;
use App\Settings\SettingsStore;

/**
 * A settings store in an array, so the rest of the app can be tested without a
 * database.
 *
 * It has to behave exactly like `DatabaseSettingsStore`, which is why
 * `tests/Feature/Settings/SettingsStoreTest.php` runs every expectation
 * against both.
 */
final class InMemorySettingsStore implements SettingsStore
{
    /** @var array<string, mixed> */
    private array $values = [];

    /** @param  array<string, mixed>  $values */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            return SettingKey::defaultFor($key, $default);
        }

        return $this->values[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->values[$key]);
    }
}
