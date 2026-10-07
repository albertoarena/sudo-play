<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * What came back from reading a catalog file.
 *
 * `catalog` is null when the whole file was rejected, which is the signal to
 * keep the last good catalog. Otherwise the catalog holds everything that
 * passed validation, however many rows were dropped along the way.
 *
 * Errors are rows that were dropped. Warnings are rows that were kept and
 * are worth mentioning, such as a field from a newer catalog format. The
 * caller decides what to do with both: the artisan command prints them, the
 * sync in M3 logs them. Nothing in this namespace writes to a log.
 */
final readonly class CatalogParseResult
{
    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public ?Catalog $catalog,
        public array $errors = [],
        public array $warnings = [],
    ) {}
}
