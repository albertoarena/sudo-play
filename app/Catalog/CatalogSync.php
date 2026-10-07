<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;

/**
 * Keeps the stored catalog up to date. Requirements 4.4.
 *
 * Two jobs, and the difference between them matters:
 *
 * - `loadBundledIfMissing()` runs on a first start, when the database is empty.
 *   It takes whatever the bundled file holds, even a catalog with no playlists,
 *   because the window still needs the genres and the moods to draw its chips
 *   and there is no older catalog to protect.
 * - `sync()` downloads the latest file. It replaces what is stored only when
 *   what arrived is better than nothing, so a broken file, an unreadable
 *   version or a catalog that ends up with no playlists all leave the app
 *   exactly as it was. That is the rule in requirements 4.3, and it is the
 *   reason a bad pull request cannot take the catalog away from everybody who
 *   has already installed the app.
 *
 * A failure is never shown to the user. The app is meant to work offline, so
 * keeping the catalog it already has is the correct behaviour, not an error.
 * The reason goes to the log for whoever is debugging.
 *
 * The source is a dependency rather than something read from config inline, so
 * a second catalog later is an additive change. Requirements 11.2.
 */
final readonly class CatalogSync
{
    public function __construct(
        private CatalogDownloader $downloader,
        private CatalogParser $parser,
        private CatalogStore $catalogs,
        private CatalogSource $bundled,
        private SettingsStore $settings,
        private LoggerInterface $log,
    ) {}

    /**
     * Puts the bundled catalog in place when nothing is stored yet, so the first
     * window has something to draw before any network call has finished.
     */
    public function loadBundledIfMissing(): SyncResult
    {
        if ($this->catalogs->current() instanceof StoredCatalog) {
            return SyncResult::notNeeded('A catalog is already stored, so the bundled copy was not needed.');
        }

        $contents = $this->bundled->contents();

        if ($contents === null) {
            return $this->keep('The bundled catalog could not be read, so the app starts with no catalog at all.');
        }

        $result = $this->parser->parse($contents);
        $this->report($result, 'bundled catalog');

        if ($result->catalog === null) {
            return $this->keep('The bundled catalog could not be parsed, so the app starts with no catalog at all.');
        }

        // No playlists is fine here. The genres and the moods are still worth
        // storing, and there is no older catalog that this could take away.
        $this->catalogs->replace($result->catalog, null);

        return SyncResult::updated(sprintf(
            'Loaded the bundled catalog: %d genres, %d moods, %d playlists.',
            count($result->catalog->genres),
            count($result->catalog->moods),
            count($result->catalog->playlists),
        ));
    }

    /**
     * Downloads the catalog and replaces the stored one, if what arrived is good
     * enough to replace it.
     */
    public function sync(): SyncResult
    {
        $url = $this->settings->get(SettingKey::CatalogUrl->value);

        if (! is_string($url) || $url === '') {
            return $this->keep('No catalog URL is configured, so nothing was downloaded.');
        }

        $download = $this->downloader->download($url);

        if (! $download->succeeded()) {
            return $this->keep(sprintf('The catalog could not be downloaded: %s', (string) $download->failure));
        }

        $result = $this->parser->parse((string) $download->body);
        $this->report($result, 'downloaded catalog');

        if ($result->catalog === null) {
            return $this->keep('The downloaded catalog was rejected as a whole, so the catalog already stored is kept.');
        }

        if ($result->catalog->playlists === []) {
            // Requirements 4.3. A file that parses but ends with no playlists is
            // worse than what is already here, however it got that way.
            return $this->keep('The downloaded catalog has no playlists, so the catalog already stored is kept.');
        }

        $this->catalogs->replace($result->catalog, Carbon::now()->toImmutable());

        return SyncResult::updated(sprintf(
            'Updated the catalog: %d genres, %d moods, %d playlists.',
            count($result->catalog->genres),
            count($result->catalog->moods),
            count($result->catalog->playlists),
        ));
    }

    /**
     * Writes down what the parser found. The parser never logs, so that it can
     * stay free of the framework: the caller decides, and here the caller is the
     * sync.
     */
    private function report(CatalogParseResult $result, string $what): void
    {
        foreach ($result->warnings as $warning) {
            $this->log->info(sprintf('Catalog sync: %s: %s', $what, $warning));
        }

        foreach ($result->errors as $error) {
            $this->log->warning(sprintf('Catalog sync: %s: %s', $what, $error));
        }
    }

    private function keep(string $message): SyncResult
    {
        $this->log->warning('Catalog sync: '.$message);

        return SyncResult::keptPrevious($message);
    }
}
