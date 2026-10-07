<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * One playlist in the catalog, already validated and normalised.
 *
 * The catalog stores a web link. What is kept here is the bare Spotify id, so
 * the two forms the app needs are built on demand and both always agree.
 */
final readonly class Playlist
{
    /**
     * @param  list<string>  $moods  ids of the moods this playlist fits, at least one
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $spotifyId,
        public string $genre,
        public array $moods,
        public ?string $curator = null,
    ) {}

    public function webUrl(): string
    {
        return SpotifyUrlNormaliser::webUrl($this->spotifyId);
    }

    public function spotifyUri(): string
    {
        return SpotifyUrlNormaliser::uri($this->spotifyId);
    }
}
