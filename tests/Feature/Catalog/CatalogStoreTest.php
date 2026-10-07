<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\CatalogSerialiser;
use App\Catalog\CatalogStore;
use App\Catalog\DatabaseCatalogStore;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;
use App\Models\CatalogSnapshot;
use Tests\Support\InMemoryCatalogStore;

/**
 * As with the settings, the fake has to behave like the real store, so every
 * expectation runs against both.
 */
dataset('catalog stores', [
    'database' => [DatabaseCatalogStore::class],
    'in memory' => [InMemoryCatalogStore::class],
]);

function storedCatalog(string $playlistId = 'rock-debugging-01'): Catalog
{
    return new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: 'Rock')],
        moods: [new Mood(id: 'debugging', label: 'Debugging', emoji: '🐛')],
        playlists: [
            new Playlist(
                id: $playlistId,
                title: 'Long investigations',
                spotifyId: 'aaaaaaaaaaaaaaaaaaaaaa',
                genre: 'rock',
                moods: ['debugging'],
                curator: 'albertoarena',
            ),
        ],
    );
}

/** @param  class-string<CatalogStore>  $store */
function catalogStore(string $store): CatalogStore
{
    return app($store);
}

it('holds nothing before anything is stored', function (string $store): void {
    expect(catalogStore($store)->current())->toBeNull();
})->with('catalog stores');

it('gives back the catalog it was given', function (string $store): void {
    $catalogs = catalogStore($store);
    $catalog = storedCatalog();

    $catalogs->replace($catalog, null);

    expect($catalogs->current()?->catalog)->toEqual($catalog);
})->with('catalog stores');

it('records when the catalog was synced', function (string $store): void {
    $catalogs = catalogStore($store);
    $syncedAt = new DateTimeImmutable('2026-10-07 08:30:00');

    $catalogs->replace(storedCatalog(), $syncedAt);

    expect($catalogs->current()?->syncedAt?->getTimestamp())->toBe($syncedAt->getTimestamp());
})->with('catalog stores');

it('leaves the sync date empty for a catalog that was never synced', function (string $store): void {
    $catalogs = catalogStore($store);

    $catalogs->replace(storedCatalog(), null);

    expect($catalogs->current()?->syncedAt)->toBeNull();
})->with('catalog stores');

it('replaces the catalog rather than keeping both', function (string $store): void {
    $catalogs = catalogStore($store);

    $catalogs->replace(storedCatalog('first'), null);
    $catalogs->replace(storedCatalog('second'), new DateTimeImmutable('2026-10-07 09:00:00'));

    $current = $catalogs->current();

    expect($current?->catalog->playlists[0]->id)->toBe('second')
        ->and($current?->syncedAt?->format('Y-m-d H:i'))->toBe('2026-10-07 09:00');
})->with('catalog stores');

it('stores a catalog that has no playlists', function (string $store): void {
    $catalogs = catalogStore($store);
    $empty = new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: 'Rock')],
        moods: [new Mood(id: 'debugging', label: 'Debugging')],
        playlists: [],
    );

    $catalogs->replace($empty, null);

    expect($catalogs->current()?->catalog)->toEqual($empty);
})->with('catalog stores');

it('keeps one row in the database, however often the catalog is replaced', function (): void {
    $catalogs = new DatabaseCatalogStore(new CatalogSerialiser);

    $catalogs->replace(storedCatalog('first'), null);
    $catalogs->replace(storedCatalog('second'), null);
    $catalogs->replace(storedCatalog('third'), null);

    expect(CatalogSnapshot::query()->count())->toBe(1);
});

it('holds nothing when the stored payload is damaged', function (): void {
    $catalogs = new DatabaseCatalogStore(new CatalogSerialiser);
    $catalogs->replace(storedCatalog(), null);

    CatalogSnapshot::query()->update(['payload' => ['version' => 99]]);

    expect($catalogs->current())->toBeNull();
});

it('resolves the database store for the rest of the app', function (): void {
    expect(app(CatalogStore::class))->toBeInstanceOf(DatabaseCatalogStore::class);
});
