<?php

declare(strict_types=1);

namespace App\Settings;

/**
 * How the user wants a playlist to start. Requirements 6.
 *
 * This lives beside the settings rather than beside the players, because it is
 * the set of values the `player_mode` setting accepts. M4 maps each case to a
 * player, and M6 uses the cases to validate what the settings window saves.
 */
enum PlayerMode: string
{
    /** The default: tell the Spotify app to play the playlist. */
    case Autoplay = 'autoplay';

    /** Open the playlist in the Spotify app and let the user press play. */
    case OpenApp = 'open-app';

    /** Open the playlist in the default browser. */
    case Web = 'web';

    /** How the settings window names the mode. */
    public function label(): string
    {
        return match ($this) {
            self::Autoplay => 'Spotify app, autoplay',
            self::OpenApp => 'Spotify app, open only',
            self::Web => 'Web player',
        };
    }
}
