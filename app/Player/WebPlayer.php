<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;
use Throwable;

/**
 * Opens the playlist in the default browser. Player mode 3 in requirements 6,
 * and the fallback for the other two.
 */
final readonly class WebPlayer implements PlayerInterface
{
    public function __construct(private UrlOpener $opener) {}

    public function play(Playlist $playlist): PlayResult
    {
        try {
            $this->opener->open($playlist->webUrl());
        } catch (Throwable $exception) {
            return PlayResult::failed('Could not open the playlist.', $exception->getMessage());
        }

        return PlayResult::played();
    }
}
