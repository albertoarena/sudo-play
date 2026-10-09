<?php

declare(strict_types=1);

namespace App\Native;

use App\MenuBar\SettingsWindow;
use Native\Laravel\Facades\Window;

/**
 * The settings window, as a normal macOS window with a title bar.
 *
 * Electron keeps one window per id. Asked to open an id that is already open,
 * it shows and focuses that window, so a second click on Settings never makes
 * a second window.
 */
final class NativeSettingsWindow implements SettingsWindow
{
    public const string ID = 'settings';

    public const int WIDTH = 440;

    public const int HEIGHT = 680;

    public function open(): void
    {
        Window::open(self::ID)
            ->route('settings')
            ->title('Sudo Play Settings')
            ->width(self::WIDTH)
            ->height(self::HEIGHT)
            ->resizable(false)
            ->minimizable(false)
            ->maximizable(false)
            ->fullscreenable(false);
    }
}
