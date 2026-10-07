<?php

declare(strict_types=1);

namespace App\Rotation;

use App\Catalog\Playlist;

/**
 * One playlist, chosen for one day and one pair of genre and mood.
 *
 * The genre and the mood travel with the pick because the caller does not
 * always know them: "surprise me" chooses the pair itself. The source says
 * whether the search had to widen, which is what the note in the window is
 * built from.
 */
final readonly class Pick
{
    public function __construct(
        public Playlist $playlist,
        public string $genre,
        public string $mood,
        public PickSource $source,
    ) {}

    public function isFallback(): bool
    {
        return $this->source !== PickSource::Exact;
    }
}
