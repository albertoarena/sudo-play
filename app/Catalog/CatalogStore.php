<?php

declare(strict_types=1);

namespace App\Catalog;

use DateTimeImmutable;

/**
 * Where the active catalog lives between runs.
 *
 * There is only ever one. A replacement is whole: the new catalog has already
 * been validated by the time it gets here, and the sync is the thing that
 * decides whether a download is good enough to replace what is stored.
 */
interface CatalogStore
{
    /** The active catalog, or null when nothing has been stored yet. */
    public function current(): ?StoredCatalog;

    /**
     * Puts this catalog in place of whatever was there.
     *
     * @param  DateTimeImmutable|null  $syncedAt  when the catalog was downloaded,
     *                                            or null for the bundled copy, which never was
     */
    public function replace(Catalog $catalog, ?DateTimeImmutable $syncedAt): void;
}
