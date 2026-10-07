<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * A logger that keeps what it was told, so a test can prove the sync said why
 * it kept the old catalog instead of failing silently.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string}> */
    public array $lines = [];

    /** @param  array<array-key, mixed>  $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->lines[] = [
            'level' => is_string($level) ? $level : 'unknown',
            'message' => (string) $message,
        ];
    }

    /** Every message logged, whatever the level, as one string. */
    public function everything(): string
    {
        return implode("\n", array_column($this->lines, 'message'));
    }
}
