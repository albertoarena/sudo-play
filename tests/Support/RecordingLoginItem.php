<?php

declare(strict_types=1);

namespace Tests\Support;

use App\SettingsWindow\LoginItem;
use RuntimeException;

/**
 * A login item that keeps every change it was asked for, so the settings
 * window can be tested without an Electron process answering.
 *
 * Built with `failing()` it refuses every change, the way the real one does
 * when NativePHP is not listening.
 */
final class RecordingLoginItem implements LoginItem
{
    /** @var list<bool> */
    public array $changes = [];

    private function __construct(private readonly bool $fails) {}

    public static function working(): self
    {
        return new self(fails: false);
    }

    public static function failing(): self
    {
        return new self(fails: true);
    }

    public function set(bool $open): void
    {
        if ($this->fails) {
            throw new RuntimeException('Electron is not listening');
        }

        $this->changes[] = $open;
    }
}
