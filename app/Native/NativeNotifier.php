<?php

declare(strict_types=1);

namespace App\Native;

use App\Notifications\Notifier;
use App\Notifications\Reminder;
use Native\Laravel\Facades\Notification;

/**
 * Shows a reminder as a native macOS notification, through NativePHP.
 *
 * Every reminder carries the same reference. Electron hands it back when the
 * notification is clicked, which is how a click on the reminder is told apart
 * from a click on any other notification.
 */
final class NativeNotifier implements Notifier
{
    public const string REFERENCE = 'sudo-play-daily-reminder';

    public function notify(Reminder $reminder): void
    {
        Notification::new()
            ->reference(self::REFERENCE)
            ->title($reminder->title)
            ->message($reminder->body)
            ->show();
    }
}
