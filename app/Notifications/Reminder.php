<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * One notification, ready to send: what the banner says and nothing about how
 * it gets there.
 */
final readonly class Reminder
{
    public function __construct(
        public string $title,
        public string $body,
    ) {}
}
