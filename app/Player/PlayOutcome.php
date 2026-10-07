<?php

declare(strict_types=1);

namespace App\Player;

enum PlayOutcome: string
{
    /** The playlist started, or opened, the way the user asked. */
    case Played = 'played';

    /** Nothing opened. */
    case Failed = 'failed';
}
