<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * A pretend Spotify behind `osascript` and `open`, for `Process::fake()`.
 *
 * It answers the commands the autoplay player sends the way the real app
 * does, and keeps every command it was sent. A call that would hang moves the
 * clock on by the call's own timeout and then times out, as a real one would.
 */
final class FakeSpotify
{
    public const string IS_RUNNING = 'application "Spotify" is running';

    public const string PLAYER_STATE = 'tell application "Spotify" to player state';

    /** @var list<list<string>> */
    public array $ran = [];

    private bool $installed = true;

    private bool $starts = true;

    private bool $denied = false;

    /** How many state queries hang before Spotify answers. */
    private int $hangingQueries = 0;

    /** How many play events Spotify ignores before it plays. */
    private int $ignoredPlays = 0;

    private int $plays = 0;

    private bool $playing = false;

    private function __construct(
        private bool $running,
        private readonly FakeClock $clock,
    ) {}

    public static function running(FakeClock $clock): self
    {
        return new self(true, $clock);
    }

    public static function closed(FakeClock $clock): self
    {
        return new self(false, $clock);
    }

    /** Fakes `Process` with this Spotify behind every command. */
    public function fake(): self
    {
        Process::fake(['*' => fn (PendingProcess $process): ProcessResult => $this->answer($process)]);

        return $this;
    }

    public function answeringAfter(int $hangingQueries): self
    {
        $this->hangingQueries = $hangingQueries;

        return $this;
    }

    public function neverAnswering(): self
    {
        return $this->answeringAfter(PHP_INT_MAX);
    }

    public function ignoringPlays(int $plays): self
    {
        $this->ignoredPlays = $plays;

        return $this;
    }

    public function notInstalled(): self
    {
        $this->installed = false;

        return $this;
    }

    public function neverStarting(): self
    {
        $this->starts = false;

        return $this;
    }

    public function denying(): self
    {
        $this->denied = true;

        return $this;
    }

    /** @return list<list<string>> */
    public function launches(): array
    {
        return array_values(array_filter($this->ran, fn (array $command): bool => $command[0] === 'open'));
    }

    /** @return list<string> */
    public function scripts(): array
    {
        $scripts = array_filter($this->ran, fn (array $command): bool => $command[0] === 'osascript');

        return array_values(array_map(fn (array $command): string => $command[2], $scripts));
    }

    public function playEvents(): int
    {
        return count(array_filter($this->scripts(), fn (string $script): bool => str_contains($script, 'play track')));
    }

    public function stateQueries(): int
    {
        return count(array_filter($this->scripts(), fn (string $script): bool => $script === self::PLAYER_STATE));
    }

    private function answer(PendingProcess $process): ProcessResult
    {
        $command = $process->command;

        if (! is_array($command)) {
            throw new RuntimeException('Expected an argument list, got: '.$command);
        }

        /** @var list<string> $command */
        $this->ran[] = $command;

        if ($command[0] === 'open') {
            return $this->launch();
        }

        $script = $command[2] ?? '';

        if ($script === self::IS_RUNNING) {
            return Process::result(output: $this->running ? "true\n" : "false\n");
        }

        if (! $this->running) {
            return Process::result(errorOutput: "execution error: Spotify got an error: Application isn't running. (-600)", exitCode: 1);
        }

        if ($this->denied) {
            return Process::result(errorOutput: 'execution error: Not authorized to send Apple events to Spotify. (-1743)', exitCode: 1);
        }

        if ($script === self::PLAYER_STATE) {
            return $this->state((int) $process->timeout);
        }

        if (str_starts_with($script, 'tell application "Spotify" to play track ')) {
            $this->plays++;
            $this->playing = $this->plays > $this->ignoredPlays;

            return Process::result();
        }

        throw new RuntimeException('Spotify was not expecting: '.$script);
    }

    private function launch(): ProcessResult
    {
        if (! $this->installed) {
            return Process::result(errorOutput: 'Unable to find application named \'Spotify\'', exitCode: 1);
        }

        if ($this->starts) {
            $this->running = true;
        }

        return Process::result();
    }

    private function state(int $timeout): ProcessResult
    {
        if ($this->hangingQueries > 0) {
            $this->hangingQueries--;
            $this->clock->advance($timeout);

            throw new ProcessTimedOutException(new SymfonyProcess(['osascript']), ProcessTimedOutException::TYPE_GENERAL);
        }

        return Process::result(output: $this->playing ? "playing\n" : "stopped\n");
    }
}
