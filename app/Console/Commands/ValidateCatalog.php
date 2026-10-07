<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalog\CatalogParser;
use App\Catalog\ReleaseGate;
use Illuminate\Console\Command;

/**
 * Validates a catalog file and exits non-zero when something is wrong, so CI
 * can tell a contributor what to fix without anyone reading the output by hand.
 *
 * With --release it also applies the release gate from requirements 10.1.
 * Normal pull request validation does not, because an empty playlist list is
 * a fine state for `main` during development.
 */
final class ValidateCatalog extends Command
{
    protected $signature = 'catalog:validate
        {path? : Path to the catalog file, relative to the project root}
        {--release : Also fail on an empty catalog or a leftover TODO}';

    protected $description = 'Check a catalog file and report every problem found';

    private const string DEFAULT_PATH = 'catalog/playlists.yaml';

    public function handle(CatalogParser $parser): int
    {
        $path = $this->argument('path');
        $path = is_string($path) && $path !== '' ? $path : self::DEFAULT_PATH;

        $full = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);

        if (! is_file($full)) {
            $this->components->error(sprintf('There is no catalog file at %s.', $path));

            return self::FAILURE;
        }

        $contents = file_get_contents($full);

        if ($contents === false) {
            $this->components->error(sprintf('The catalog file at %s could not be read.', $path));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Checking %s', $path));

        $result = $parser->parse($contents);

        foreach ($result->warnings as $warning) {
            $this->components->warn($warning);
        }

        foreach ($result->errors as $error) {
            $this->components->error($error);
        }

        $catalog = $result->catalog;

        if ($catalog === null) {
            $this->components->error('The file was rejected as a whole, so nothing was loaded. A running app would keep the catalog it already had.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Genres', (string) count($catalog->genres));
        $this->components->twoColumnDetail('Moods', (string) count($catalog->moods));
        $this->components->twoColumnDetail('Playlists', (string) count($catalog->playlists));

        if ($result->errors !== []) {
            $this->components->error(sprintf(
                '%s dropped. Fix the rows above and run this again.',
                count($result->errors) === 1 ? '1 row was' : count($result->errors).' rows were',
            ));

            return self::FAILURE;
        }

        if ($this->option('release') === true) {
            $problems = ReleaseGate::problems($catalog);

            foreach ($problems as $problem) {
                $this->components->error($problem);
            }

            if ($problems !== []) {
                $this->components->error('This catalog is valid but not releasable.');

                return self::FAILURE;
            }
        }

        $this->components->success(sprintf(
            'The catalog is valid: %d genres, %d moods, %d playlists.',
            count($catalog->genres),
            count($catalog->moods),
            count($catalog->playlists),
        ));

        return self::SUCCESS;
    }
}
