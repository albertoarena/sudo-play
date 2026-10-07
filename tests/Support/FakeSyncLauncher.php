<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Catalog\SyncLauncher;

/**
 * A launcher that only counts, so a test can prove the app asks for a sync at
 * startup without an Electron process answering.
 */
final class FakeSyncLauncher implements SyncLauncher
{
    public int $launches = 0;

    public function launch(): void
    {
        $this->launches++;
    }
}
