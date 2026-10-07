<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;
use Throwable;

/**
 * Opens the playlist in the Spotify app and lets the user press play. Player
 * mode 2 in requirements 6.
 *
 * The operating system routes the `spotify:` URI to the app. When Spotify is not
 * installed it may still accept the URI and show its own error, which this
 * class cannot see. Only a failure to ask at all comes back as a failed result.
 */
final readonly class OpenAppPlayer implements PlayerInterface
{
    public function __construct(private UrlOpener $opener) {}

    public function play(Playlist $playlist): PlayResult
    {
        try {
            $this->opener->open($playlist->spotifyUri());
        } catch (Throwable $exception) {
            return PlayResult::failed('Could not open Spotify.', $exception->getMessage());
        }

        return PlayResult::played();
    }
}
