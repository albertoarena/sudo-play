<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Player\UrlOpener;
use RuntimeException;

/**
 * An opener that keeps the links it was asked to open, so a player can be
 * tested without an Electron process answering.
 *
 * Built with `failing()` it refuses every link, the way the real one does when
 * NativePHP is not listening.
 */
final class RecordingUrlOpener implements UrlOpener
{
    /** @var list<string> */
    public array $opened = [];

    private function __construct(private readonly bool $fails) {}

    public static function working(): self
    {
        return new self(fails: false);
    }

    public static function failing(): self
    {
        return new self(fails: true);
    }

    public function open(string $url): void
    {
        if ($this->fails) {
            throw new RuntimeException('Electron is not listening');
        }

        $this->opened[] = $url;
    }
}
