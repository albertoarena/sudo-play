<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * What a sync did.
 *
 * Three outcomes, because the app behaves differently for each. Only `Updated`
 * means the window has something new to show, and only `KeptPrevious` is worth
 * a line in the log.
 */
enum SyncOutcome: string
{
    /** The stored catalog was replaced. */
    case Updated = 'updated';

    /**
     * Something was wrong with what arrived, so the catalog already in place is
     * still in place and its date is untouched. The user is not told: an app
     * that works offline has nothing to apologise for. Requirements 4.3.
     */
    case KeptPrevious = 'kept-previous';

    /** There was nothing to do, because a catalog was already loaded. */
    case NotNeeded = 'not-needed';
}
