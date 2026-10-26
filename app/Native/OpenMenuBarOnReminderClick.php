<?php

declare(strict_types=1);

namespace App\Native;

use Native\Desktop\Events\Notifications\NotificationClicked;
use Native\Desktop\Facades\MenuBar;

/**
 * Opens the menu bar window when the daily reminder is clicked.
 * Requirements 7.3.
 *
 * NativePHP turns the click into this event, with the reference the
 * notification was sent with, so a click on any other notification is ignored.
 */
final class OpenMenuBarOnReminderClick
{
    public function handle(NotificationClicked $event): void
    {
        if ($event->reference === NativeNotifier::REFERENCE) {
            MenuBar::show();
        }
    }
}
