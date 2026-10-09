<?php

declare(strict_types=1);

namespace App\MenuBar;

/**
 * Opens the settings window. Requirements 7.2.
 *
 * Opening a window needs NativePHP, so the menu bar asks through this
 * interface and its tests can count the calls.
 */
interface SettingsWindow
{
    /** Opens the window, or brings it to the front when it is already open. */
    public function open(): void;
}
