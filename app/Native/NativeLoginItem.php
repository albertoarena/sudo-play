<?php

declare(strict_types=1);

namespace App\Native;

use App\SettingsWindow\LoginItem;
use Native\Desktop\Facades\App;

/**
 * Adds the app to the macOS login items, or takes it off, through Electron's
 * `app.setLoginItemSettings`.
 */
final class NativeLoginItem implements LoginItem
{
    public function set(bool $open): void
    {
        App::openAtLogin($open);
    }
}
