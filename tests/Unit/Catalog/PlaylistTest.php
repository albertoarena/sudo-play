<?php

declare(strict_types=1);

use App\Catalog\Playlist;

function aPlaylist(?string $curator = 'octocat'): Playlist
{
    return new Playlist(
        id: 'rock-friday-deploy-01',
        title: 'Riffs for risky releases',
        spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
        genre: 'rock',
        moods: ['friday-deploy', 'flow-state'],
        curator: $curator,
    );
}

it('keeps what it was given', function (): void {
    expect(aPlaylist())
        ->id->toBe('rock-friday-deploy-01')
        ->title->toBe('Riffs for risky releases')
        ->spotifyId->toBe('aB1cD2eF3gH4iJ5kL6mN7o')
        ->genre->toBe('rock')
        ->moods->toBe(['friday-deploy', 'flow-state'])
        ->curator->toBe('octocat');
});

it('has no curator by default', function (): void {
    $playlist = new Playlist(
        id: 'rock-flow-state-01',
        title: 'Anonymous riffs',
        spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
        genre: 'rock',
        moods: ['flow-state'],
    );

    expect($playlist->curator)->toBeNull();
});

it('builds the web url from the stored spotify id', function (): void {
    expect(aPlaylist()->webUrl())->toBe('https://open.spotify.com/playlist/aB1cD2eF3gH4iJ5kL6mN7o');
});

it('builds the spotify uri from the stored spotify id', function (): void {
    expect(aPlaylist()->spotifyUri())->toBe('spotify:playlist:aB1cD2eF3gH4iJ5kL6mN7o');
});

it('cannot be changed after it is built', function (): void {
    $playlist = aPlaylist();

    expect(fn (): string => $playlist->title = 'Something else')->toThrow(Error::class);
});
