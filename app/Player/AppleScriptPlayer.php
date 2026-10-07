<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

/**
 * Tells the Spotify app to play the playlist. Player mode 1 in requirements 6,
 * and the default.
 *
 * The script runs through `osascript` with the command as an argument list, so
 * no shell ever reads it. The URI is built from a validated 22 character id,
 * so it cannot break out of the quotes either.
 */
final class AppleScriptPlayer implements PlayerInterface
{
    /**
     * `tell application "Spotify"` waits for Spotify to finish launching, and
     * the user is waiting on the Play button meanwhile. A cold start takes a few
     * seconds; past this, the browser is the better answer.
     */
    public const int TIMEOUT_SECONDS = 8;

    /** The AppleScript error number for "Not authorized to send Apple events". */
    private const string NOT_AUTHORISED = '(-1743)';

    public function play(Playlist $playlist): PlayResult
    {
        $script = sprintf('tell application "Spotify" to play track "%s"', $playlist->spotifyUri());

        try {
            $result = Process::timeout(self::TIMEOUT_SECONDS)->run(['osascript', '-e', $script]);
        } catch (ProcessTimedOutException $exception) {
            return PlayResult::failed('Spotify took too long to start.', $exception->getMessage());
        }

        if ($result->successful()) {
            return PlayResult::played();
        }

        $error = trim($result->errorOutput());

        if (str_contains($error, self::NOT_AUTHORISED)) {
            return PlayResult::failed(
                'Sudo Play is not allowed to control Spotify. You can allow it in System Settings, Privacy & Security, Automation.',
                $error,
            );
        }

        return PlayResult::failed('Could not start Spotify.', $error);
    }
}
