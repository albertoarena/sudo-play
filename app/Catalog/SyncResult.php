<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * What a sync did, and a sentence saying so.
 *
 * The message is for the `catalog:sync` command and the log. The window never
 * shows it: the outcome is all the UI needs, because keeping the old catalog is
 * normal behaviour rather than an error to report.
 */
final readonly class SyncResult
{
    public function __construct(
        public SyncOutcome $outcome,
        public string $message,
    ) {}

    public static function updated(string $message): self
    {
        return new self(SyncOutcome::Updated, $message);
    }

    public static function keptPrevious(string $message): self
    {
        return new self(SyncOutcome::KeptPrevious, $message);
    }

    public static function notNeeded(string $message): self
    {
        return new self(SyncOutcome::NotNeeded, $message);
    }
}
