<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;

/**
 * Starts a playlist. Requirements 6 and 11.3.
 *
 * Nothing outside `app/Player/` knows which service plays the music, so a later
 * player for another service is an additional class, not a change to callers.
 */
interface PlayerInterface
{
    /** Never throws: a failure is a result, because the window has to say something. */
    public function play(Playlist $playlist): PlayResult;
}
