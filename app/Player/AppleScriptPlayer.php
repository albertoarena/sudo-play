<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

/**
 * Tells the Spotify app to play the playlist. Player mode 1 in requirements 6,
 * and the default.
 *
 * A play event sent while Spotify launches is lost: Spotify finishes starting
 * with nothing playing. So a closed Spotify is started first, without the
 * event, and asked a harmless question until it answers. Only then does the
 * play event go, and the player checks that Spotify really plays. Spotify often
 * ignores the first event after a start, so it gets one more, and no more.
 *
 * Every command runs as an argument list, so no shell ever reads it. The URI
 * is built from a validated 22 character id, so it cannot break out of the
 * quotes either. Each call has its own short timeout, so a call that hangs
 * cannot use up the whole wait.
 */
final readonly class AppleScriptPlayer implements PlayerInterface
{
    /** How long a cold start may take, in all, before the user is asked to press Play again. */
    public const int START_BUDGET_SECONDS = 20;

    /** The pause between two questions to Spotify. */
    public const float POLL_INTERVAL_SECONDS = 0.5;

    /** The timeout for a question, and for launching the app. */
    public const int QUERY_TIMEOUT_SECONDS = 2;

    /** The timeout for the play event. */
    public const int PLAY_TIMEOUT_SECONDS = 5;

    private const string IS_RUNNING = 'application "Spotify" is running';

    private const string PLAYER_STATE = 'tell application "Spotify" to player state';

    /** The AppleScript error number for "Not authorized to send Apple events". */
    private const string NOT_AUTHORISED = '(-1743)';

    public function __construct(private Clock $clock = new SystemClock) {}

    public function play(Playlist $playlist): PlayResult
    {
        $deadline = $this->clock->now() + self::START_BUDGET_SECONDS;

        if (! $this->running()) {
            $failure = $this->launch($deadline);

            if ($failure !== null) {
                return $failure;
            }
        }

        return $this->start($playlist, $deadline);
    }

    /**
     * Starts Spotify in the background, so the menu bar window keeps its
     * focus, and waits until it answers. Null means it answered.
     */
    private function launch(float $deadline): ?PlayResult
    {
        $launched = $this->run(['open', '-g', '-a', 'Spotify'], self::QUERY_TIMEOUT_SECONDS);

        if ($launched !== null && $launched->failed()) {
            return $this->failure($launched);
        }

        while (true) {
            $answer = $this->ask(self::PLAYER_STATE);

            if ($answer?->successful()) {
                return null;
            }

            if ($answer !== null && $this->denied($answer)) {
                return $this->failure($answer);
            }

            if ($this->clock->now() + self::POLL_INTERVAL_SECONDS >= $deadline) {
                return $this->running()
                    ? PlayResult::starting('Spotify did not answer within '.self::START_BUDGET_SECONDS.' seconds.')
                    : PlayResult::failed('Spotify did not start.', 'Spotify was not running '.self::START_BUDGET_SECONDS.' seconds after the launch.');
            }

            $this->clock->sleep(self::POLL_INTERVAL_SECONDS);
        }
    }

    /** Sends the play event and checks that it took. One more try when it did not. */
    private function start(Playlist $playlist, float $deadline): PlayResult
    {
        $script = sprintf('tell application "Spotify" to play track "%s"', $playlist->spotifyUri());

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $sent = $this->run(['osascript', '-e', $script], self::PLAY_TIMEOUT_SECONDS);

            if ($sent === null) {
                return PlayResult::starting('The play event timed out after '.self::PLAY_TIMEOUT_SECONDS.' seconds.');
            }

            if ($sent->failed()) {
                return $this->failure($sent);
            }

            $this->clock->sleep(self::POLL_INTERVAL_SECONDS);

            if (trim($this->ask(self::PLAYER_STATE)?->output() ?? '') === 'playing') {
                return PlayResult::played();
            }

            if ($this->clock->now() >= $deadline) {
                break;
            }
        }

        return PlayResult::starting('Spotify answered but did not start playing.');
    }

    /** Asks without starting Spotify. Any answer other than yes counts as no. */
    private function running(): bool
    {
        return trim($this->ask(self::IS_RUNNING)?->output() ?? '') === 'true';
    }

    private function ask(string $script): ?ProcessResult
    {
        return $this->run(['osascript', '-e', $script], self::QUERY_TIMEOUT_SECONDS);
    }

    /**
     * Null means the command timed out.
     *
     * @param  list<string>  $command
     */
    private function run(array $command, int $timeout): ?ProcessResult
    {
        try {
            return Process::timeout($timeout)->run($command);
        } catch (ProcessTimedOutException) {
            return null;
        }
    }

    private function denied(ProcessResult $result): bool
    {
        return str_contains($result->errorOutput(), self::NOT_AUTHORISED);
    }

    private function failure(ProcessResult $result): PlayResult
    {
        $error = trim($result->errorOutput());

        if ($this->denied($result)) {
            return PlayResult::failed(
                'Sudo Play is not allowed to control Spotify. You can allow it in System Settings, Privacy & Security, Automation.',
                $error,
            );
        }

        return PlayResult::failed('Could not start Spotify.', $error);
    }
}
