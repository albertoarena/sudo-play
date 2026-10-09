<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Notifications\Notifier;
use App\Notifications\Reminder;
use RuntimeException;

/**
 * A notifier that keeps what it was asked to show, so the reminder can be
 * tested without an Electron process answering.
 *
 * Built with `failing()` it refuses every reminder, the way the real one does
 * when NativePHP is not listening.
 */
final class RecordingNotifier implements Notifier
{
    /** @var list<Reminder> */
    public array $shown = [];

    private function __construct(private readonly bool $fails) {}

    public static function working(): self
    {
        return new self(fails: false);
    }

    public static function failing(): self
    {
        return new self(fails: true);
    }

    public function notify(Reminder $reminder): void
    {
        if ($this->fails) {
            throw new RuntimeException('Electron is not listening');
        }

        $this->shown[] = $reminder;
    }
}
