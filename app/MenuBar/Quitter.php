<?php

declare(strict_types=1);

namespace App\MenuBar;

/**
 * Quits the app, for the Quit link in the footer. Requirements 7.1.
 *
 * A menu bar app has no Dock icon and no app menu, so this link is the only
 * way out short of Activity Monitor. The implementation is a NativePHP facade
 * and lives in `app/Native/`.
 */
interface Quitter
{
    public function quit(): void;
}
