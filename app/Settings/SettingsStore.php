<?php

declare(strict_types=1);

namespace App\Settings;

/**
 * The one way in and out of a setting. Requirements 11.1.
 *
 * No controller, player, picker or Vue page reads or writes a setting any other
 * way. That is what keeps per user preferences possible later: an implementation
 * can scope these keys by profile and no caller has to change.
 *
 * The interface is deliberately flat, a key to a value, rather than a method per
 * setting. `SettingKey` names the keys the app uses and owns their defaults.
 */
interface SettingsStore
{
    /**
     * The stored value, or the default when nothing has been stored.
     *
     * For a key in `SettingKey` the default is that setting's own, whatever the
     * caller passes. For any other key it is `$fallback`.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Stores a value, replacing whatever was there.
     *
     * The value has to survive a round trip through JSON, so strings, numbers,
     * booleans, null and arrays of those. Nothing in the app stores anything else.
     */
    public function set(string $key, mixed $value): void;

    /** Removes a value, so the key answers with its default again. */
    public function forget(string $key): void;
}
