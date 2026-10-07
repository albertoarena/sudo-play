<?php

declare(strict_types=1);

namespace App\Player;

enum PlayOutcome: string
{
    /** The playlist started, or opened, the way the user asked. */
    case Played = 'played';

    /** The chosen way did not work, so the playlist opened in the browser. */
    case FellBack = 'fell-back';

    /** Nothing opened. */
    case Failed = 'failed';
}
