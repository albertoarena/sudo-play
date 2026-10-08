<?php

declare(strict_types=1);

namespace App\MenuBar;

use App\Rotation\CalendarDate;

/**
 * Which day it is on the user's Mac. Requirements 5.
 *
 * The pick changes at local midnight, not at UTC midnight, and the app itself
 * runs in UTC. This is the one place that answers the question, so a test can
 * fix the day and the menu bar can never read a clock of its own.
 */
interface Today
{
    public function today(): CalendarDate;
}
