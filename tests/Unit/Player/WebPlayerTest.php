<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Player\PlayOutcome;
use App\Player\WebPlayer;
use Tests\Support\RecordingUrlOpener;

function webPlaylist(): Playlist
{
    return new Playlist(
        id: 'rock-friday-deploy-01',
        title: 'Riffs for risky releases',
        spotifyId: '37i9dQZF1DXcBWIGoYBM5M',
        genre: 'rock',
        moods: ['friday-deploy'],
    );
}

it('opens the https link of the playlist', function (): void {
    $opener = RecordingUrlOpener::working();

    $result = (new WebPlayer($opener))->play(webPlaylist());

    expect($opener->opened)->toBe(['https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M'])
        ->and($result->outcome)->toBe(PlayOutcome::Played)
        ->and($result->message)->toBeNull();
});

it('reports a failure when the link cannot be opened', function (): void {
    $result = (new WebPlayer(RecordingUrlOpener::failing()))->play(webPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Could not open the playlist.')
        ->and($result->reason)->toContain('Electron is not listening');
});
