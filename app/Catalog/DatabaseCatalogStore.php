<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Models\CatalogSnapshot;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The catalog in the SQLite database NativePHP keeps outside the app bundle, so
 * it survives an app update and a sync is not needed on every install.
 */
final readonly class DatabaseCatalogStore implements CatalogStore
{
    public function __construct(
        private CatalogSerialiser $serialiser,
    ) {}

    public function current(): ?StoredCatalog
    {
        $snapshot = CatalogSnapshot::query()->latest('id')->first();

        if (! $snapshot instanceof CatalogSnapshot) {
            return null;
        }

        $catalog = $this->serialiser->fromArray($snapshot->payload);

        if ($catalog === null) {
            // The row is damaged. Reporting no catalog sends the caller to the
            // bundled copy, which is a working app rather than a broken one.
            return null;
        }

        $syncedAt = $snapshot->synced_at;

        return new StoredCatalog(
            catalog: $catalog,
            syncedAt: $syncedAt instanceof DateTimeImmutable ? $syncedAt : null,
        );
    }

    public function replace(Catalog $catalog, ?DateTimeImmutable $syncedAt): void
    {
        DB::transaction(function () use ($catalog, $syncedAt): void {
            CatalogSnapshot::query()->delete();

            CatalogSnapshot::query()->create([
                'payload' => $this->serialiser->toArray($catalog),
                'synced_at' => $syncedAt,
            ]);
        });
    }
}
