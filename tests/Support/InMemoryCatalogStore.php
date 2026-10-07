<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Catalog\Catalog;
use App\Catalog\CatalogStore;
use App\Catalog\StoredCatalog;
use DateTimeImmutable;

/**
 * A catalog store in memory, so the sync can be tested without a database.
 *
 * It has to behave like `DatabaseCatalogStore`, which is why
 * `tests/Feature/Catalog/CatalogStoreTest.php` runs every expectation against
 * both.
 */
final class InMemoryCatalogStore implements CatalogStore
{
    private ?StoredCatalog $stored = null;

    /** How many times the catalog has been replaced, so a test can prove it was not. */
    public int $replacements = 0;

    public function __construct(?StoredCatalog $stored = null)
    {
        $this->stored = $stored;
    }

    public function current(): ?StoredCatalog
    {
        return $this->stored;
    }

    public function replace(Catalog $catalog, ?DateTimeImmutable $syncedAt): void
    {
        $this->stored = new StoredCatalog(catalog: $catalog, syncedAt: $syncedAt);
        $this->replacements++;
    }
}
