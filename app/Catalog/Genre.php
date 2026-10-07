<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A music genre the user can pick. Defined in the catalog, so adding one
 * needs no code change.
 */
final readonly class Genre
{
    public function __construct(
        public string $id,
        public string $label,
    ) {}
}
