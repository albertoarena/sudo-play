<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/*
 * `xcrun stapler` asks Apple's ticket service online, and on GitHub's runner
 * that connection drops now and then ("The network connection was lost",
 * error 68). One drop failed the whole release twice on 2026-10-15, after both
 * notarizations were accepted. So release.sh sends every stapler call through
 * `retry` in scripts/macos/retry.sh.
 */

/**
 * Runs `retry` from retry.sh in bash with no pause, around a command that fails
 * a given number of times before it succeeds.
 */
function runRetry(int $failures): Process
{
    $counter = tempnam(sys_get_temp_dir(), 'retry');

    $script = <<<'BASH'
        set -euo pipefail
        source "$1"
        flaky() {
            local count; count=$(( $(cat "$2") + 1 )); echo "$count" > "$2"
            [ "$count" -gt "$3" ] || { echo "attempt $count failed" >&2; return 68; }
            echo "attempt $count worked"
        }
        retry flaky "$@"
        BASH;

    $process = new Process(
        ['bash', '-c', $script, 'retry-test', base_path('scripts/macos/retry.sh'), $counter, (string) $failures],
        env: ['SUDO_PLAY_RETRY_PAUSE' => '0'],
    );
    $process->run();

    unlink($counter);

    return $process;
}

it('passes at once when the command works', function (): void {
    $process = runRetry(0);

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('attempt 1 worked');
});

it('tries again when the command fails', function (): void {
    $process = runRetry(2);

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('attempt 3 worked');
});

it('gives up after five tries with the command exit code', function (): void {
    $process = runRetry(5);

    expect($process->getExitCode())->toBe(68)
        ->and($process->getErrorOutput())->toContain('attempt 5 failed')
        ->not->toContain('attempt 6');
});

it('sends every stapler call in release.sh through retry', function (): void {
    $script = (string) file_get_contents(base_path('scripts/macos/release.sh'));

    preg_match_all('/^.*\bstapler\b.*$/m', $script, $matches);
    $calls = array_values(array_filter($matches[0], fn (string $line): bool => ! str_starts_with(ltrim($line), '#')));

    expect($calls)->toHaveCount(4)
        ->each->toStartWith('retry xcrun stapler ');

    expect($script)->toContain('source "$root/scripts/macos/retry.sh"');
});
