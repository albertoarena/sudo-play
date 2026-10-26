<?php

declare(strict_types=1);

namespace App\Native;

use App\Player\UrlOpener;
use Native\Desktop\Facades\Shell;

/**
 * Opens a link through Electron's `shell.openExternal`, which hands it to macOS.
 *
 * NativePHP returns nothing from this call, so a link macOS has no app for is
 * not reported back. Only a failure to reach Electron at all throws, which is
 * what lets the players fall back.
 */
final class ShellUrlOpener implements UrlOpener
{
    public function open(string $url): void
    {
        Shell::openExternal($url);
    }
}
