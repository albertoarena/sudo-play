<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A validated catalog: the genres and moods the user can pick, and the
 * playlists that survived validation.
 *
 * Holding zero playlists is a normal state during development. What to do
 * about it is the caller's decision, not the catalog's.
 */
final readonly class Catalog
{
    /**
     * @param  list<Genre>  $genres
     * @param  list<Mood>  $moods
     * @param  list<Playlist>  $playlists
     */
    public function __construct(
        public int $version,
        public array $genres,
        public array $moods,
        public array $playlists,
    ) {}
}
