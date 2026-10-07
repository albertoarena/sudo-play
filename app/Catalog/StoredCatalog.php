<?php

declare(strict_types=1);

namespace App\Catalog;

use DateTimeImmutable;

/**
 * The catalog the app is running on, and when it last came off the network.
 *
 * The two travel together because the window shows both: the genres, moods and
 * playlists, and the footer date. Requirements 4.4.
 *
 * `syncedAt` is null when the catalog has never been downloaded, which is the
 * state after a first run with no network. There is no date to show then, and
 * inventing one would tell the user their catalog is fresher than it is.
 */
final readonly class StoredCatalog
{
    public function __construct(
        public Catalog $catalog,
        public ?DateTimeImmutable $syncedAt = null,
    ) {}
}
