<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * The bundled catalog, read from the file that ships with the app.
 *
 * `base_path()` is correct in both `native:serve` and a packaged app, which is
 * the question M3 had to settle. NativePHP starts PHP with the Laravel project
 * root as its working directory either way: in development that is the project
 * on disk, and in a packaged app it is the copy inside the bundle, unpacked
 * from the archive. The build copies the whole project apart from the patterns
 * in `cleanup_exclude_files`, and `catalog` is not one of them, so the file is
 * there under the same relative path.
 *
 * `bundled()` is the one place that decides where the file lives. The
 * constructor takes a path so a test can point somewhere else.
 */
final readonly class BundledCatalogSource implements CatalogSource
{
    public const string RELATIVE_PATH = 'catalog/playlists.yaml';

    public function __construct(
        private string $path,
    ) {}

    public static function bundled(): self
    {
        return new self(base_path(self::RELATIVE_PATH));
    }

    public function contents(): ?string
    {
        if (! is_file($this->path)) {
            return null;
        }

        $contents = file_get_contents($this->path);

        return $contents === false ? null : $contents;
    }
}
