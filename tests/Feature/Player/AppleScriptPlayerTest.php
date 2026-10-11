<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Player\AppleScriptPlayer;
use App\Player\PlayOutcome;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\Support\FakeClock;
use Tests\Support\FakeSpotify;

const PLAY_SCRIPT = 'tell application "Spotify" to play track "spotify:playlist:37i9dQZF1DWWQRwui0ExPn"';

const DENIED_MESSAGE = 'Sudo Play is not allowed to control Spotify. You can allow it in System Settings, Privacy & Security, Automation.';

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

describe('with Spotify running', function (): void {
    it('plays at once, without launching it', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::running($clock)->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Played)
            ->and($spotify->launches())->toBe([])
            ->and($spotify->scripts())->toBe([FakeSpotify::IS_RUNNING, PLAY_SCRIPT, FakeSpotify::PLAYER_STATE]);
    });

    it('sends the script as an argument list, so no shell reads it', function (): void {
        $clock = new FakeClock;
        FakeSpotify::running($clock)->fake();

        (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['osascript', '-e', PLAY_SCRIPT]);
    });

    it('sends the play event once more when Spotify ignores it', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::running($clock)->ignoringPlays(1)->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Played)
            ->and($spotify->playEvents())->toBe(2);
    });

    it('stops after the second play event, and says Spotify is still starting', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::running($clock)->ignoringPlays(5)->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Starting)
            ->and($result->message)->toBeNull()
            ->and($spotify->playEvents())->toBe(2);
    });

    it('says so when macOS does not allow the app to control Spotify', function (): void {
        $clock = new FakeClock;
        FakeSpotify::running($clock)->denying()->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Failed)
            ->and($result->message)->toBe(DENIED_MESSAGE)
            ->and($result->reason)->toContain('(-1743)');
    });
});

describe('with Spotify closed', function (): void {
    it('launches it in the background, as an argument list, without the play event', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->fake();

        (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($spotify->launches())->toBe([['open', '-g', '-a', 'Spotify']])
            ->and($spotify->ran[1])->toBe(['open', '-g', '-a', 'Spotify']);
    });

    it('waits until Spotify answers, then plays', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->answeringAfter(3)->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Played)
            ->and($spotify->playEvents())->toBe(1)
            ->and($spotify->scripts())->toBe([
                FakeSpotify::IS_RUNNING,
                FakeSpotify::PLAYER_STATE,
                FakeSpotify::PLAYER_STATE,
                FakeSpotify::PLAYER_STATE,
                FakeSpotify::PLAYER_STATE,
                PLAY_SCRIPT,
                FakeSpotify::PLAYER_STATE,
            ]);
    });

    it('asks about every half second, each time with its own short timeout', function (): void {
        $clock = new FakeClock;
        FakeSpotify::closed($clock)->answeringAfter(2)->fake();

        (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($clock->sleeps)->each->toBe(AppleScriptPlayer::POLL_INTERVAL_SECONDS)
            ->and(AppleScriptPlayer::POLL_INTERVAL_SECONDS)->toBe(0.5)
            ->and(AppleScriptPlayer::QUERY_TIMEOUT_SECONDS)->toBeLessThanOrEqual(3);
        Process::assertRan(fn (PendingProcess $process): bool => $process->command === ['osascript', '-e', FakeSpotify::PLAYER_STATE]
            && $process->timeout === AppleScriptPlayer::QUERY_TIMEOUT_SECONDS);
    });

    it('plays when Spotify ignores the first play event after it started', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->answeringAfter(2)->ignoringPlays(1)->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Played)
            ->and($spotify->playEvents())->toBe(2);
    });

    it('says Spotify is still starting when it is running but has not answered in time', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->neverAnswering()->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Starting)
            ->and($result->message)->toBeNull()
            ->and($spotify->playEvents())->toBe(0)
            ->and($clock->now())->toBeGreaterThanOrEqual(AppleScriptPlayer::START_BUDGET_SECONDS - 1)
            ->and($clock->now())->toBeLessThanOrEqual(AppleScriptPlayer::START_BUDGET_SECONDS + AppleScriptPlayer::QUERY_TIMEOUT_SECONDS);
    });

    it('waits about 20 seconds in all', function (): void {
        expect(AppleScriptPlayer::START_BUDGET_SECONDS)->toBeGreaterThanOrEqual(15)->toBeLessThanOrEqual(25);
    });

    it('reports a failure when Spotify is not installed', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->notInstalled()->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Failed)
            ->and($result->message)->toBe('Could not start Spotify.')
            ->and($result->reason)->toContain('Unable to find application')
            ->and($spotify->stateQueries())->toBe(0);
    });

    it('reports a failure when Spotify does not start at all', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->neverStarting()->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Failed)
            ->and($result->message)->toBe('Spotify did not start.')
            ->and($spotify->playEvents())->toBe(0);
    });

    it('stops waiting when macOS does not allow the app to control Spotify', function (): void {
        $clock = new FakeClock;
        $spotify = FakeSpotify::closed($clock)->denying()->fake();

        $result = (new AppleScriptPlayer($clock))->play(scriptPlaylist());

        expect($result->outcome)->toBe(PlayOutcome::Failed)
            ->and($result->message)->toBe(DENIED_MESSAGE)
            ->and($spotify->stateQueries())->toBe(1);
    });
});
