<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;

function smallCatalog(): Catalog
{
    return new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: 'Rock')],
        moods: [new Mood(id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀', description: 'Be brave.')],
        playlists: [new Playlist(
            id: 'rock-friday-deploy-01',
            title: 'Riffs for risky releases',
            spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
            genre: 'rock',
            moods: ['friday-deploy'],
        )],
    );
}

it('keeps the version, genres, moods and playlists it was given', function (): void {
    $catalog = smallCatalog();

    expect($catalog->version)->toBe(1)
        ->and($catalog->genres)->toHaveCount(1)
        ->and($catalog->genres[0]->label)->toBe('Rock')
        ->and($catalog->moods)->toHaveCount(1)
        ->and($catalog->moods[0]->emoji)->toBe('🚀')
        ->and($catalog->playlists)->toHaveCount(1)
        ->and($catalog->playlists[0]->id)->toBe('rock-friday-deploy-01');
});

it('can hold genres and moods with no playlists', function (): void {
    $catalog = new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: 'Rock')],
        moods: [new Mood(id: 'flow-state', label: 'Flow State')],
        playlists: [],
    );

    expect($catalog->playlists)->toBe([]);
});

it('gives a mood an empty emoji and description when the catalog omits them', function (): void {
    $mood = new Mood(id: 'flow-state', label: 'Flow State');

    expect($mood->emoji)->toBe('')
        ->and($mood->description)->toBe('');
});

it('cannot be changed after it is built', function (): void {
    $catalog = smallCatalog();

    expect(fn (): int => $catalog->version = 2)->toThrow(Error::class);
});
