<?php

declare(strict_types=1);

namespace App\MenuBar;

/**
 * Hides the menu bar window, for Esc. Requirements 7.1.
 *
 * Hiding the window needs NativePHP, so the menu bar asks through this
 * interface and its tests can count the calls. The implementation lives in
 * `app/Native/`.
 */
interface MenuBarWindow
{
    /** Hides the window. A click on the menu bar icon shows it again. */
    public function hide(): void;
}
