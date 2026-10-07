<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Starts a sync without waiting for it.
 *
 * Requirements 4.4 says the startup sync never blocks the UI: the window
 * renders from the stored catalog first and the download happens after. That
 * means the sync cannot run inside the request that draws the window, so
 * something has to start it elsewhere and return at once.
 *
 * It is an interface because the only thing that can do that is a NativePHP
 * facade, and `app/Catalog/` stays free of NativePHP. The implementation lives
 * in `app/Native/`.
 */
interface SyncLauncher
{
    /** Starts a sync and returns immediately. */
    public function launch(): void;
}
