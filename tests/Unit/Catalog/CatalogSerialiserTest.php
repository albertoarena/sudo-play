<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\CatalogSerialiser;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;

function sampleCatalog(): Catalog
{
    return new Catalog(
        version: 1,
        genres: [
            new Genre(id: 'rock', label: 'Rock'),
            new Genre(id: 'jazz', label: 'Jazz'),
        ],
        moods: [
            new Mood(id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀', description: 'Be brave.'),
            new Mood(id: 'debugging', label: 'Debugging'),
        ],
        playlists: [
            new Playlist(
                id: 'rock-friday-deploy-01',
                title: 'Riffs for risky releases',
                spotifyId: 'aaaaaaaaaaaaaaaaaaaaaa',
                genre: 'rock',
                moods: ['friday-deploy', 'debugging'],
                curator: 'albertoarena',
            ),
            new Playlist(
                id: 'jazz-debugging-01',
                title: 'Slow lines for long bugs',
                spotifyId: 'bbbbbbbbbbbbbbbbbbbbbb',
                genre: 'jazz',
                moods: ['debugging'],
            ),
        ],
    );
}

it('brings a catalog back unchanged', function (): void {
    $catalog = sampleCatalog();

    $restored = (new CatalogSerialiser)->fromArray((new CatalogSerialiser)->toArray($catalog));

    expect($restored)->toEqual($catalog);
});

it('brings back a catalog that has no playlists', function (): void {
    $catalog = new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: 'Rock')],
        moods: [new Mood(id: 'debugging', label: 'Debugging')],
        playlists: [],
    );

    $serialiser = new CatalogSerialiser;

    expect($serialiser->fromArray($serialiser->toArray($catalog)))->toEqual($catalog);
});

it('writes a shape that is readable without the app', function (): void {
    $payload = (new CatalogSerialiser)->toArray(sampleCatalog());

    expect($payload['version'])->toBe(1)
        ->and($payload['genres'][0])->toBe(['id' => 'rock', 'label' => 'Rock'])
        ->and($payload['moods'][1])->toBe([
            'id' => 'debugging',
            'label' => 'Debugging',
            'emoji' => '',
            'description' => '',
        ])
        ->and($payload['playlists'][1])->toBe([
            'id' => 'jazz-debugging-01',
            'title' => 'Slow lines for long bugs',
            'spotify_id' => 'bbbbbbbbbbbbbbbbbbbbbb',
            'genre' => 'jazz',
            'moods' => ['debugging'],
            'curator' => null,
        ]);
});

it('survives a trip through json', function (): void {
    $serialiser = new CatalogSerialiser;
    $catalog = sampleCatalog();

    $json = json_encode($serialiser->toArray($catalog), JSON_THROW_ON_ERROR);

    expect($serialiser->fromArray(json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR)))
        ->toEqual($catalog);
});

it('reads nothing from something that is not a catalog', function (mixed $payload): void {
    expect((new CatalogSerialiser)->fromArray($payload))->toBeNull();
})->with([
    'null' => [null],
    'a string' => ['rock'],
    'a list' => [[1, 2, 3]],
    'an empty array' => [[]],
    'no version' => [[['genres' => [], 'moods' => [], 'playlists' => []]]],
    'a version it cannot read' => [['version' => 2, 'genres' => [], 'moods' => [], 'playlists' => []]],
    'genres that are not a list' => [['version' => 1, 'genres' => 'rock', 'moods' => [], 'playlists' => []]],
    'a genre missing its label' => [['version' => 1, 'genres' => [['id' => 'rock']], 'moods' => [], 'playlists' => []]],
    'a playlist missing its spotify id' => [[
        'version' => 1,
        'genres' => [],
        'moods' => [],
        'playlists' => [['id' => 'a', 'title' => 'A', 'genre' => 'rock', 'moods' => ['debugging']]],
    ]],
    'a playlist whose moods are not strings' => [[
        'version' => 1,
        'genres' => [],
        'moods' => [],
        'playlists' => [[
            'id' => 'a',
            'title' => 'A',
            'spotify_id' => 'aaaaaaaaaaaaaaaaaaaaaa',
            'genre' => 'rock',
            'moods' => [1, 2],
        ]],
    ]],
]);
