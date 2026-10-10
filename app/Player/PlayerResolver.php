<?php

declare(strict_types=1);

namespace App\Player;

use App\Catalog\Playlist;
use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\Settings\SettingsStore;
use Psr\Log\LoggerInterface;

/**
 * The player the rest of the app talks to. Requirements 6.
 *
 * It picks a player from the `player_mode` setting on every play, so a change in
 * the settings window needs no restart. When the chosen player fails, it opens
 * the playlist in the browser instead and says why in one sentence. The raw
 * error goes to the log, where it is useful, and not to the window, where it
 * is not.
 */
final readonly class PlayerResolver implements PlayerInterface
{
    private const string FALLBACK_NOTE = 'Opened the playlist in your browser instead.';

    public function __construct(
        private SettingsStore $settings,
        private AppleScriptPlayer $autoplay,
        private OpenAppPlayer $openApp,
        private WebPlayer $web,
        private LoggerInterface $log,
    ) {}

    /**
     * The player for the current setting. A value that is not a player mode,
     * from a hand edited database, means the default rather than a crash.
     */
    public function resolve(): PlayerInterface
    {
        return $this->player($this->mode());
    }

    /** Every result names the mode the setting chose, so the window can say what happened. */
    public function play(Playlist $playlist): PlayResult
    {
        $mode = $this->mode();

        return $this->attempt($this->player($mode), $playlist)->ranAs($mode);
    }

    private function attempt(PlayerInterface $player, Playlist $playlist): PlayResult
    {
        $result = $player->play($playlist);

        if ($result->outcome !== PlayOutcome::Failed) {
            return $result;
        }

        $this->log->warning('Could not play "'.$playlist->id.'": '.$result->reason);

        if ($player === $this->web) {
            return $result;
        }

        $fallback = $this->web->play($playlist);

        if ($fallback->outcome === PlayOutcome::Failed) {
            $this->log->warning('The browser fallback failed too: '.$fallback->reason);

            return $fallback;
        }

        return PlayResult::fellBack($result->message.' '.self::FALLBACK_NOTE, (string) $result->reason);
    }

    private function mode(): PlayerMode
    {
        $stored = $this->settings->get(SettingKey::PlayerMode->value);

        return (is_string($stored) ? PlayerMode::tryFrom($stored) : null) ?? PlayerMode::Autoplay;
    }

    private function player(PlayerMode $mode): PlayerInterface
    {
        return match ($mode) {
            PlayerMode::Autoplay => $this->autoplay,
            PlayerMode::OpenApp => $this->openApp,
            PlayerMode::Web => $this->web,
        };
    }
}
