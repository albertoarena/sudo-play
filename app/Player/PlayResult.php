<?php

declare(strict_types=1);

namespace App\Player;

use App\Settings\PlayerMode;

/**
 * What happened when the user pressed Play.
 *
 * The message is a short sentence for the window, and is null when there is
 * nothing to say. The reason is for the log: it carries the raw error, which
 * means nothing to the user. Requirements 6.
 *
 * The mode is the one the setting chose. A single player does not know it, so
 * it is null until the resolver adds it. A fallback keeps the chosen mode, not
 * the browser, so the outcome alone says what happened.
 */
final readonly class PlayResult
{
    private function __construct(
        public PlayOutcome $outcome,
        public ?string $message,
        public ?string $reason,
        public ?PlayerMode $mode = null,
    ) {}

    /** The same result, as played in a mode. */
    public function ranAs(PlayerMode $mode): self
    {
        return new self($this->outcome, $this->message, $this->reason, $mode);
    }

    public static function played(): self
    {
        return new self(PlayOutcome::Played, message: null, reason: null);
    }

    public static function fellBack(string $message, string $reason): self
    {
        return new self(PlayOutcome::FellBack, $message, $reason);
    }

    public static function failed(string $message, string $reason): self
    {
        return new self(PlayOutcome::Failed, $message, $reason);
    }
}
