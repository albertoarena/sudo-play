<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A developer mood the user can pick. Defined in the catalog, so adding one
 * needs no code change.
 *
 * The emoji and the description are optional. A catalog that gives only an id
 * and a label still works, the UI just has less to show.
 */
final readonly class Mood
{
    public function __construct(
        public string $id,
        public string $label,
        public string $emoji = '',
        public string $description = '',
    ) {}
}
