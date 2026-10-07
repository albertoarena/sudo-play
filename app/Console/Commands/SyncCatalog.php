<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalog\CatalogStore;
use App\Catalog\CatalogSync;
use App\Catalog\SyncOutcome;
use App\Catalog\SyncResult;
use Illuminate\Console\Command;

/**
 * Loads the bundled catalog when there is none, then downloads the latest one.
 *
 * This is the command the scheduler runs once a day and the one the app starts
 * in its own process just after launch, so the window never waits for the
 * network. Requirements 4.4.
 *
 * It exits non-zero only when the app ends up with no catalog at all. A download
 * that failed is not a failure of this command: the app is meant to work
 * offline, and keeping the catalog already in place is the correct answer.
 */
final class SyncCatalog extends Command
{
    protected $signature = 'catalog:sync';

    protected $description = 'Download the latest catalog, keeping the current one if anything is wrong';

    public function handle(CatalogSync $sync, CatalogStore $catalogs): int
    {
        $this->report($sync->loadBundledIfMissing());
        $this->report($sync->sync());

        $current = $catalogs->current();

        if ($current === null) {
            $this->components->error('There is no catalog to play from. The bundled copy could not be read and no download succeeded.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Genres', (string) count($current->catalog->genres));
        $this->components->twoColumnDetail('Moods', (string) count($current->catalog->moods));
        $this->components->twoColumnDetail('Playlists', (string) count($current->catalog->playlists));
        $this->components->twoColumnDetail(
            'Last synced',
            $current->syncedAt?->format('Y-m-d H:i') ?? 'never, this is the bundled copy',
        );

        return self::SUCCESS;
    }

    private function report(SyncResult $result): void
    {
        match ($result->outcome) {
            SyncOutcome::Updated => $this->components->info($result->message),
            SyncOutcome::KeptPrevious => $this->components->warn($result->message),
            SyncOutcome::NotNeeded => $this->components->info($result->message),
        };
    }
}
