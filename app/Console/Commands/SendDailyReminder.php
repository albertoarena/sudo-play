<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Catalog\CatalogStore;
use App\MenuBar\Today;
use App\Notifications\DailyReminder;
use App\Notifications\Notifier;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Shows the daily reminder when it is due. Requirements 7.3.
 *
 * The scheduler runs this every minute, and DailyReminder says yes in one
 * minute a day at most. A notification that cannot be shown is a log line: the
 * next chance is tomorrow, and a crash here would only fill the scheduler log.
 */
final class SendDailyReminder extends Command
{
    protected $signature = 'reminder:send';

    protected $description = 'Show the daily reminder if it is due now';

    public function handle(DailyReminder $reminder, CatalogStore $catalogs, Today $today, Notifier $notifier, LoggerInterface $log): int
    {
        $due = $reminder->due($today->now(), $catalogs->current()?->catalog);

        if ($due === null) {
            return self::SUCCESS;
        }

        try {
            $notifier->notify($due);
        } catch (Throwable $exception) {
            $log->warning('Could not show the daily reminder: '.$exception->getMessage());
        }

        return self::SUCCESS;
    }
}
