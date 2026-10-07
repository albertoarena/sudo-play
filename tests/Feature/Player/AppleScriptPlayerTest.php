<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Player\AppleScriptPlayer;
use App\Player\PlayOutcome;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process as SymfonyProcess;

function scriptPlaylist(): Playlist
{
    return new Playlist(
        id: 'lofi-flow-state-01',
        title: 'Beats for deep work',
        spotifyId: '37i9dQZF1DWWQRwui0ExPn',
        genre: 'lofi',
        moods: ['flow-state'],
    );
}

it('tells Spotify to play the playlist uri', function (): void {
    Process::fake();

    $result = (new AppleScriptPlayer)->play(scriptPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Played);
    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [
        'osascript',
        '-e',
        'tell application "Spotify" to play track "spotify:playlist:37i9dQZF1DWWQRwui0ExPn"',
    ]);
});

it('gives up after a few seconds, because Spotify can hang while it launches', function (): void {
    Process::fake();

    (new AppleScriptPlayer)->play(scriptPlaylist());

    Process::assertRan(fn (PendingProcess $process): bool => $process->timeout === AppleScriptPlayer::TIMEOUT_SECONDS);
    expect(AppleScriptPlayer::TIMEOUT_SECONDS)->toBeGreaterThan(0)->toBeLessThanOrEqual(10);
});

it('reports a failure when the script fails', function (): void {
    Process::fake([
        '*' => Process::result(errorOutput: "execution error: Can't get application \"Spotify\". (-1728)", exitCode: 1),
    ]);

    $result = (new AppleScriptPlayer)->play(scriptPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Could not start Spotify.')
        ->and($result->reason)->toContain('(-1728)');
});

it('says so when macOS does not allow the app to control Spotify', function (): void {
    Process::fake([
        '*' => Process::result(errorOutput: 'execution error: Not authorized to send Apple events to Spotify. (-1743)', exitCode: 1),
    ]);

    $result = (new AppleScriptPlayer)->play(scriptPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Sudo Play is not allowed to control Spotify. You can allow it in System Settings, Privacy & Security, Automation.')
        ->and($result->reason)->toContain('(-1743)');
});

it('reports a failure when the script times out', function (): void {
    Process::fake([
        '*' => function (): never {
            throw new ProcessTimedOutException(new SymfonyProcess(['osascript']), ProcessTimedOutException::TYPE_GENERAL);
        },
    ]);

    $result = (new AppleScriptPlayer)->play(scriptPlaylist());

    expect($result->outcome)->toBe(PlayOutcome::Failed)
        ->and($result->message)->toBe('Spotify took too long to start.')
        ->and($result->reason)->toContain('exceeded the timeout');
});
