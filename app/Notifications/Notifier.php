<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * Shows a reminder to the user. The real one is a macOS notification through
 * NativePHP, so this interface is what keeps the reminder testable.
 */
interface Notifier
{
    public function notify(Reminder $reminder): void;
}
