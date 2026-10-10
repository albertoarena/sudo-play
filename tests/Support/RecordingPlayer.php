<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Catalog\Playlist;
use App\Player\PlayerInterface;
use App\Player\PlayResult;
use App\Settings\PlayerMode;

/**
 * A player that keeps what it was asked to play and answers what it was told.
 * With nothing to say, it played in the default mode, as the resolver would.
 */
final class RecordingPlayer implements PlayerInterface
{
    /** @var list<string> */
    public array $played = [];

    public function __construct(
        private readonly ?PlayResult $result = null,
    ) {}

    public function play(Playlist $playlist): PlayResult
    {
        $this->played[] = $playlist->id;

        return $this->result ?? PlayResult::played()->ranAs(PlayerMode::Autoplay);
    }
}
