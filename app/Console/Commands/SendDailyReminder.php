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
 * The scheduler runs this every minute. DailyReminder says yes until a
 * reminder is recorded as sent, so the day is recorded only after the
 * notification was shown. One that cannot be shown is a log line, and the
 * next minute in the window tries again. A crash here would only fill the
 * scheduler log.
 */
final class SendDailyReminder extends Command
{
    protected $signature = 'reminder:send';

    protected $description = 'Show the daily reminder if it is due now';

    public function handle(DailyReminder $reminder, CatalogStore $catalogs, Today $today, Notifier $notifier, LoggerInterface $log): int
    {
        $now = $today->now();
        $due = $reminder->due($now, $catalogs->current()?->catalog);

        if ($due === null) {
            return self::SUCCESS;
        }

        try {
            $notifier->notify($due);
            $reminder->sent($now);
        } catch (Throwable $exception) {
            $log->warning('Could not show the daily reminder: '.$exception->getMessage());
        }

        return self::SUCCESS;
    }
}
