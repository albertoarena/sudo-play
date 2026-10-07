<?php

declare(strict_types=1);

namespace App\Native;

use App\Catalog\SyncLauncher;
use Native\Laravel\Facades\ChildProcess;

/**
 * Runs `catalog:sync` in a PHP process of its own, through NativePHP.
 *
 * This is the one adapter M3 needs for a NativePHP facade. It sits in
 * `app/Native/` rather than in `app/Catalog/`, because the catalog code must not
 * depend on NativePHP: everything on the other side of this class can be tested
 * without an Electron process answering.
 *
 * The process is not persistent. It downloads, writes the catalog and exits. If
 * it fails there is nothing to restart: the app keeps the catalog it has and the
 * scheduler tries again tomorrow.
 */
final class ChildProcessSyncLauncher implements SyncLauncher
{
    /** NativePHP keeps one process per alias, so a second launch replaces the first. */
    public const string ALIAS = 'catalog-sync';

    public function launch(): void
    {
        ChildProcess::artisan(
            cmd: 'catalog:sync',
            alias: self::ALIAS,
            persistent: false,
        );
    }
}
