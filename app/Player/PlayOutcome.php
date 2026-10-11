<?php

declare(strict_types=1);

namespace App\Player;

enum PlayOutcome: string
{
    /** The playlist started, or opened, the way the user asked. */
    case Played = 'played';

    /**
     * Spotify is running but did not take the playlist in time. Nothing else
     * opened, because the next Play will work. Requirements 6.
     */
    case Starting = 'starting';

    /** The chosen way did not work, so the playlist opened in the browser. */
    case FellBack = 'fell-back';

    /** Nothing opened. */
    case Failed = 'failed';
}
