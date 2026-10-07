<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * The sample catalog files in `tests/fixtures/catalogs`, read as text.
 */
final class CatalogFixtures
{
    public static function contents(string $name): string
    {
        $path = dirname(__DIR__).'/fixtures/catalogs/'.$name;

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Missing catalog fixture: {$path}");
        }

        return $contents;
    }
}
