<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Player\OpenAppPlayer;
use App\Player\PlayOutcome;
use Tests\Support\RecordingUrlOpener;

function appPlaylist(): Playlist
{
    return new Playlist(
        id: 'jazz-debugging-01',
        title: 'Patient brushes',
        spotifyId: '37i9dQZF1DX0SM0LYsmbMT',
        genre: 'jazz',
        moods: ['debugging'],
    );
}

it('opens the spotify uri of the playlist', function (): void {
    $opener = RecordingUrlOpener::working();

    $result = (new OpenAppPlayer($opener))->play(appPlaylist());

    expect($opener->opened)->toBe(['spotify:playlist:37i9dQZF1DX0SM0LYsmbMT'])
        ->and($result->outcome)->toBe(PlayOutcome::Played);
});

it('reports a failure when the uri cannot be opened', function (): void {
    $result = (new OpenAppPlayer(RecordingUrlOpener::failing()))->play(appPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Could not open Spotify.')
        ->and($result->reason)->toContain('Electron is not listening');
});
