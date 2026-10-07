<?php

declare(strict_types=1);

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;
use App\Catalog\ReleaseGate;

/**
 * @param  list<Playlist>  $playlists
 */
function releasableCatalog(array $playlists, string $genreLabel = 'Rock', string $moodDescription = 'Deep focus.'): Catalog
{
    return new Catalog(
        version: 1,
        genres: [new Genre(id: 'rock', label: $genreLabel)],
        moods: [new Mood(id: 'flow-state', label: 'Flow State', emoji: '🎧', description: $moodDescription)],
        playlists: $playlists,
    );
}

function somePlaylist(string $title = 'Real riffs', ?string $curator = 'octocat'): Playlist
{
    return new Playlist(
        id: 'rock-flow-state-01',
        title: $title,
        spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
        genre: 'rock',
        moods: ['flow-state'],
        curator: $curator,
    );
}

it('passes a catalog that is ready to release', function (): void {
    expect(ReleaseGate::problems(releasableCatalog([somePlaylist()])))->toBe([]);
});

it('fails a catalog with no playlists', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([]));

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('no playlists');
});

it('fails a playlist title that still says TODO', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([somePlaylist(title: 'TODO choose one')]));

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('TODO')
        ->and($problems[0])->toContain('rock-flow-state-01');
});

it('fails a curator that still says TODO', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([somePlaylist(curator: 'TODO')]));

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('TODO');
});

it('fails a genre label that still says TODO', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([somePlaylist()], genreLabel: 'TODO'));

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('rock');
});

it('fails a mood description that still says TODO', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([somePlaylist()], moodDescription: 'TODO write this'));

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('flow-state');
});

it('reports every problem it finds, not just the first', function (): void {
    $problems = ReleaseGate::problems(releasableCatalog([], genreLabel: 'TODO'));

    expect($problems)->toHaveCount(2);
});
