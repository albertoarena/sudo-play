<?php

declare(strict_types=1);

use App\Catalog\BundledCatalogSource;
use App\Catalog\CatalogParser;
use App\Catalog\CatalogSource;

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
    ]);

    foreach ($moods as $mood) {
        expect($mood->label)->not->toBe('')
            ->and($mood->emoji)->not->toBe('')
            ->and($mood->description)->not->toBe('');
    }
});

it('ships no playlists, because the maintainer fills them in before a release', function (): void {
    $catalog = (new CatalogParser)->parse((string) BundledCatalogSource::bundled()->contents())->catalog;

    expect($catalog?->playlists)->toBe([]);
});
