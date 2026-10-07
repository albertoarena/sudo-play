<?php

declare(strict_types=1);

namespace App\Player;

/**
 * What happened when the user pressed Play.
 *
 * The message is a short sentence for the window, and is null when there is
 * nothing to say. The reason is for the log: it carries the raw error, which
 * means nothing to the user. Requirements 6.
 */
final readonly class PlayResult
{
    private function __construct(
        public PlayOutcome $outcome,
        public ?string $message,
        public ?string $reason,
    ) {}

    public static function played(): self
    {
        return new self(PlayOutcome::Played, message: null, reason: null);
    }

    public static function failed(string $message, string $reason): self
    {
        return new self(PlayOutcome::Failed, $message, $reason);
    }
}
