<?php

declare(strict_types=1);

namespace App\Native;

use App\MenuBar\MenuBarWindow;
use Native\Laravel\Client\Client;

/**
 * Hides the menu bar window through Electron.
 *
 * `MenuBar::hide()` would be the natural call, but in nativephp/laravel 1.3.1
 * it posts to `menu-bar/close`, and the Electron plugin 1.3.0 serves only
 * `menu-bar/hide`. So this posts to that route itself, through the same client
 * the facade uses. See `docs/DECISIONS.md`, 2026-10-10.
 */
final class NativeMenuBarWindow implements MenuBarWindow
{
    public function __construct(
        private readonly Client $client,
    ) {}

    public function hide(): void
    {
        $this->client->post('menu-bar/hide');
    }
}
