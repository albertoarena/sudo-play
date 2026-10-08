<?php

declare(strict_types=1);

namespace App\SettingsWindow;

/**
 * Whether macOS starts the app when the user logs in. Requirements 7.2.
 *
 * The setting is kept in the settings store like every other. This is the
 * other half: telling macOS about it, which needs NativePHP, so it sits behind
 * an interface.
 */
interface LoginItem
{
    public function set(bool $open): void;
}
