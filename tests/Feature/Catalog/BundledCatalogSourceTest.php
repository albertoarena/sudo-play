<?php

declare(strict_types=1);

use App\Catalog\BundledCatalogSource;
use App\Catalog\Catalog;
use App\Catalog\CatalogParser;
use App\Catalog\CatalogSource;
use App\Catalog\Playlist;
use App\Catalog\ReleaseGate;

it('reads the catalog that ships with the app', function (): void {
    expect(BundledCatalogSource::bundled()->contents())->toContain('version: 1');
});

it('looks for the catalog next to the rest of the app', function (): void {
    expect(BundledCatalogSource::RELATIVE_PATH)->toBe('catalog/playlists.yaml')
        ->and(base_path(BundledCatalogSource::RELATIVE_PATH))->toBeFile();
});

it('reads nothing when there is no file', function (): void {
    expect((new BundledCatalogSource(base_path('catalog/not-here.yaml')))->contents())->toBeNull();
});

it('reads nothing when the path is a directory', function (): void {
    expect((new BundledCatalogSource(base_path('catalog')))->contents())->toBeNull();
});

it('resolves the bundled source for the rest of the app', function (): void {
    expect(app(CatalogSource::class))->toBeInstanceOf(BundledCatalogSource::class)
        ->and(app(CatalogSource::class)->contents())->toContain('version: 1');
});

it('ships a catalog the app can read', function (): void {
    $contents = BundledCatalogSource::bundled()->contents();

    expect($contents)->toBeString();

    $result = (new CatalogParser)->parse((string) $contents);

    expect($result->errors)->toBe([])
        ->and($result->warnings)->toBe([])
        ->and($result->catalog)->not->toBeNull();
});

it('ships the genres from the requirements', function (): void {
    $catalog = (new CatalogParser)->parse((string) BundledCatalogSource::bundled()->contents())->catalog;

    $ids = array_map(static fn (object $genre): string => $genre->id, $catalog?->genres ?? []);

    expect($ids)->toEqualCanonicalizing([
        'classical',
        'rock',
        'pop',
        'indie',
        'electronic',
        'jazz',
        'lofi',
    ]);
});

it('ships the moods from the requirements, each with an emoji and a description', function (): void {
    $catalog = (new CatalogParser)->parse((string) BundledCatalogSource::bundled()->contents())->catalog;
    $moods = $catalog?->moods ?? [];

    $ids = array_map(static fn (object $mood): string => $mood->id, $moods);

    expect($ids)->toEqualCanonicalizing([
        'shipped-it',
        'coffee-needed',
        'blue-monday',
        'flow-state',
        'debugging',
        'friday-deploy',
        'agent-mode',
        'night-shift',
    ]);

    foreach ($moods as $mood) {
        expect($mood->label)->not->toBe('')
            ->and($mood->emoji)->not->toBe('')
            ->and($mood->description)->not->toBe('');
    }
});

function shippedCatalog(): Catalog
{
    $catalog = (new CatalogParser)->parse((string) BundledCatalogSource::bundled()->contents())->catalog;

    expect($catalog)->not->toBeNull();

    return $catalog;
}

it('ships a catalog the release gate accepts', function (): void {
    expect(ReleaseGate::problems(shippedCatalog()))->toBe([]);
});

// The first fallback is the same mood in another genre, so a mood with a
// playlist never leaves a pick empty, whatever genre is chosen.
it('ships at least one playlist for every mood', function (): void {
    $catalog = shippedCatalog();

    foreach ($catalog->moods as $mood) {
        $count = count(array_filter($catalog->playlists, static fn (Playlist $playlist): bool => in_array($mood->id, $playlist->moods, true)));

        expect($count)->toBeGreaterThan(0, "No playlist for the mood {$mood->id}");
    }
});

it('ships at least one playlist for every genre', function (): void {
    $catalog = shippedCatalog();

    foreach ($catalog->genres as $genre) {
        $count = count(array_filter($catalog->playlists, static fn (Playlist $playlist): bool => $playlist->genre === $genre->id));

        expect($count)->toBeGreaterThan(0, "No playlist for the genre {$genre->id}");
    }
});

it('ships each Spotify playlist once', function (): void {
    $spotifyIds = array_map(static fn (Playlist $playlist): string => $playlist->spotifyId, shippedCatalog()->playlists);

    expect($spotifyIds)->toBe(array_values(array_unique($spotifyIds)));
});

// These three were used to test the layout before the real playlists existed.
// Their titles were invented, and one reuses a real title, so they are caught
// by their Spotify id.
it('ships none of the playlists used for layout testing', function (): void {
    $spotifyIds = array_map(static fn (Playlist $playlist): string => $playlist->spotifyId, shippedCatalog()->playlists);

    expect($spotifyIds)->not->toContain('3f48OVuCnymSOJpbXiMZ3j')
        ->and($spotifyIds)->not->toContain('1wjjaCDYuOWIoLQLImBZWJ')
        ->and($spotifyIds)->not->toContain('3PLHdsGIEWOY2XgXIT5gS8');
});
