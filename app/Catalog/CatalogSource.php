<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * The catalog that ships inside the app, as text.
 *
 * It is the offline fallback from requirements 4.4: what a first run loads
 * before any download, and what the app falls back to if the stored catalog is
 * ever unreadable.
 *
 * One method, which hands back the YAML. Nothing else needs the path, so
 * nothing else has to know where a packaged app keeps its files.
 */
interface CatalogSource
{
    /** The bundled catalog, or null when it cannot be read. */
    public function contents(): ?string;
}
