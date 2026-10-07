<?php

declare(strict_types=1);

use App\Catalog\BundledCatalogSource;
use App\Catalog\CatalogParser;
use App\Catalog\CatalogSource;
use App\Catalog\CatalogSync;
use App\Catalog\HttpCatalogDownloader;
use App\Catalog\StoredCatalog;
use App\Catalog\SyncOutcome;
use App\Settings\SettingKey;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\CatalogFixtures;
use Tests\Support\InMemoryCatalogStore;
use Tests\Support\InMemorySettingsStore;
use Tests\Support\RecordingLogger;

/**
 * The sync runs through the real downloader with `Http::fake()`, so the timeout
 * and the size limit are part of what is tested rather than mocked away. Only
 * the catalog store, the settings and the log are fakes.
 */
beforeEach(function (): void {
    $this->catalogs = new InMemoryCatalogStore;
    $this->settings = new InMemorySettingsStore;
    $this->log = new RecordingLogger;
    $this->bundled = new BundledCatalogSource(fixturePath('valid.yaml'));
});

function fixturePath(string $name): string
{
    return dirname(__DIR__, 2).'/fixtures/catalogs/'.$name;
}

function sync(?CatalogSource $bundled = null): CatalogSync
{
    return new CatalogSync(
        downloader: new HttpCatalogDownloader,
        parser: new CatalogParser,
        catalogs: test()->catalogs,
        bundled: $bundled ?? test()->bundled,
        settings: test()->settings,
        log: test()->log,
    );
}

function fakeCatalogResponse(string $fixture): void
{
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(CatalogFixtures::contents($fixture))]);
}

/**
 * Puts a known good catalog in the store, synced a week before "now".
 *
 * It goes in through the store rather than through a sync, so the tests below
 * exercise exactly one download each: the one that has to be refused.
 */
function storePreviousCatalog(): void
{
    $previous = (new CatalogParser)->parse(CatalogFixtures::contents('valid.yaml'))->catalog;

    expect($previous)->not->toBeNull();

    test()->catalogs->replace($previous, new DateTimeImmutable('2026-10-01 07:00:00'));
    test()->travelTo('2026-10-07 08:30:00');
}

/** The stored catalog and its date are both exactly as they were. */
function previousCatalogSurvives(string $mentions = ''): void
{
    $before = test()->catalogs->current();

    expect($before)->toBeInstanceOf(StoredCatalog::class);

    $result = sync()->sync();
    $after = test()->catalogs->current();

    expect($result->outcome)->toBe(SyncOutcome::KeptPrevious)
        ->and($after?->catalog)->toEqual($before?->catalog)
        ->and($after?->syncedAt?->format('Y-m-d H:i:s'))->toBe('2026-10-01 07:00:00');

    if ($mentions !== '') {
        expect(test()->log->everything())->toContain($mentions);
    }
}

// A first run, with nothing stored

it('loads the bundled catalog on a first run', function (): void {
    $result = sync()->loadBundledIfMissing();

    expect($result->outcome)->toBe(SyncOutcome::Updated)
        ->and($this->catalogs->current()?->catalog->playlists)->toHaveCount(2)
        ->and($this->catalogs->current()?->catalog->genres)->toHaveCount(2);
});

it('leaves the sync date empty for the bundled catalog, which was never downloaded', function (): void {
    sync()->loadBundledIfMissing();

    expect($this->catalogs->current()?->syncedAt)->toBeNull();
});

it('does not load the bundled catalog over one that is already stored', function (): void {
    sync()->loadBundledIfMissing();
    $result = sync()->loadBundledIfMissing();

    expect($result->outcome)->toBe(SyncOutcome::NotNeeded)
        ->and($this->catalogs->replacements)->toBe(1);
});

it('loads a bundled catalog with no playlists, because there is nothing to protect yet', function (): void {
    $result = sync(new BundledCatalogSource(fixturePath('no-playlists.yaml')))->loadBundledIfMissing();

    expect($result->outcome)->toBe(SyncOutcome::Updated)
        ->and($this->catalogs->current()?->catalog->playlists)->toBe([])
        ->and($this->catalogs->current()?->catalog->genres)->toHaveCount(1);
});

it('stores nothing and says why when the bundled catalog is missing', function (): void {
    $result = sync(new BundledCatalogSource('/does/not/exist.yaml'))->loadBundledIfMissing();

    expect($result->outcome)->toBe(SyncOutcome::KeptPrevious)
        ->and($this->catalogs->current())->toBeNull()
        ->and($this->log->everything())->toContain('bundled');
});

it('stores nothing when the bundled catalog cannot be parsed', function (): void {
    $result = sync(new BundledCatalogSource(fixturePath('broken-yaml.yaml')))->loadBundledIfMissing();

    expect($result->outcome)->toBe(SyncOutcome::KeptPrevious)
        ->and($this->catalogs->current())->toBeNull();
});

it('makes no network call while loading the bundled catalog', function (): void {
    Http::fake();

    sync()->loadBundledIfMissing();

    Http::assertNothingSent();
});

// A download the app accepts

it('replaces the catalog and records when it was synced', function (): void {
    $this->travelTo('2026-10-07 08:30:00');
    sync()->loadBundledIfMissing();
    fakeCatalogResponse('unknown-field.yaml');

    $result = sync()->sync();

    expect($result->outcome)->toBe(SyncOutcome::Updated)
        ->and($this->catalogs->current()?->catalog->playlists[0]->title)->toBe('From a newer catalog format')
        ->and($this->catalogs->current()?->syncedAt?->format('Y-m-d H:i:s'))->toBe('2026-10-07 08:30:00');
});

it('downloads from the url in the settings', function (): void {
    $this->settings->set(SettingKey::CatalogUrl->value, 'https://example.test/fork/playlists.yaml');
    Http::fake(['https://example.test/fork/playlists.yaml' => Http::response(CatalogFixtures::contents('valid.yaml'))]);

    expect(sync()->sync()->outcome)->toBe(SyncOutcome::Updated);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.test/fork/playlists.yaml');
});

it('downloads from the catalog-live branch when the settings say nothing', function (): void {
    fakeCatalogResponse('valid.yaml');

    sync()->sync();

    Http::assertSent(fn (Request $request): bool => $request->url() === SettingKey::DEFAULT_CATALOG_URL);
});

it('keeps the playlists that are valid and drops the rest', function (): void {
    fakeCatalogResponse('invalid-playlists.yaml');

    $result = sync()->sync();
    $playlists = $this->catalogs->current()?->catalog->playlists ?? [];

    expect($result->outcome)->toBe(SyncOutcome::Updated)
        ->and($playlists)->toHaveCount(1)
        ->and($playlists[0]->title)->toBe('The only good one')
        ->and($this->log->everything())->toContain('unknown genre');
});

it('reports a field it did not understand without dropping the playlist', function (): void {
    fakeCatalogResponse('unknown-field.yaml');

    $result = sync()->sync();

    expect($result->outcome)->toBe(SyncOutcome::Updated)
        ->and($this->catalogs->current()?->catalog->playlists)->toHaveCount(1)
        ->and($this->log->everything())->toContain('tier');
});

// A download the app refuses, where the stored catalog has to survive untouched

it('keeps the previous catalog when the whole file is rejected', function (): void {
    storePreviousCatalog();
    fakeCatalogResponse('broken-yaml.yaml');

    previousCatalogSurvives('not valid YAML');
});

it('keeps the previous catalog when the version is one it cannot read', function (): void {
    storePreviousCatalog();
    fakeCatalogResponse('wrong-version.yaml');

    previousCatalogSurvives('version');
});

it('keeps the previous catalog when the download has no valid playlists', function (): void {
    storePreviousCatalog();
    fakeCatalogResponse('no-playlists.yaml');

    previousCatalogSurvives('no playlists');
});

it('keeps the previous catalog when every playlist in the download is invalid', function (): void {
    storePreviousCatalog();
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(
        "version: 1\ngenres:\n  - id: rock\n    label: Rock\nmoods:\n  - id: debugging\n    label: Debugging\nplaylists:\n  - id: bad\n    title: Unknown genre\n    url: https://open.spotify.com/playlist/aB1cD2eF3gH4iJ5kL6mN7o\n    genre: nope\n    moods: [debugging]\n",
    )]);

    previousCatalogSurvives('no playlists');
});

it('keeps the previous catalog when the network is down', function (): void {
    storePreviousCatalog();
    Http::fake(fn (): never => throw new ConnectionException('Could not resolve host'));

    previousCatalogSurvives('Could not resolve host');
});

it('keeps the previous catalog when the download times out', function (): void {
    storePreviousCatalog();
    Http::fake(fn (): never => throw new ConnectionException('Operation timed out after 5000 milliseconds'));

    previousCatalogSurvives('timed out');
});

it('keeps the previous catalog when the url answers 404', function (): void {
    storePreviousCatalog();
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response('Not Found', 404)]);

    previousCatalogSurvives('404');
});

it('keeps the previous catalog when the download is over the size limit', function (): void {
    storePreviousCatalog();
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(
        str_repeat('a', HttpCatalogDownloader::SIZE_LIMIT_BYTES + 1),
    )]);

    previousCatalogSurvives('1 MB');
});

it('keeps the previous catalog when the response is empty', function (): void {
    storePreviousCatalog();
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response('')]);

    previousCatalogSurvives();
});
