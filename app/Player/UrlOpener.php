<?php

declare(strict_types=1);

namespace App\Player;

/**
 * Hands a link to the operating system, which opens it in whatever app owns it:
 * the default browser for https, the Spotify app for a `spotify:` URI.
 *
 * It is an interface because the only thing that can do this is a NativePHP
 * facade. The implementation lives in `app/Native/`, so the players can be
 * tested with a fake.
 */
interface UrlOpener
{
    /** Opens the link, or throws when the operating system could not be asked. */
    public function open(string $url): void;
}
