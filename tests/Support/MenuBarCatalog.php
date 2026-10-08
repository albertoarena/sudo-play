<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Catalog\Catalog;
use App\Catalog\Genre;
use App\Catalog\Mood;
use App\Catalog\Playlist;

/**
 * A small catalog with a hole in every place the menu bar has to explain.
 *
 * - rock and friday-deploy: two playlists, so Next has somewhere to go
 * - lofi and debugging: no lofi playlist, so the same mood in another genre
 * - rock and flow-state: no flow-state playlist, so another rock mood
 * - lofi and flow-state: nothing at all
 */
final class MenuBarCatalog
{
    public static function full(): Catalog
    {
        return new Catalog(
            version: 1,
            genres: self::genres(),
            moods: self::moods(),
            playlists: [
                new Playlist(
                    id: 'rock-friday-deploy-01',
                    title: 'Riffs for risky releases',
                    spotifyId: 'aB1cD2eF3gH4iJ5kL6mN7o',
                    genre: 'rock',
                    moods: ['friday-deploy'],
                    curator: 'octocat',
                ),
                new Playlist(
                    id: 'rock-friday-deploy-02',
                    title: 'Rollback anthems',
                    spotifyId: 'bB1cD2eF3gH4iJ5kL6mN7o',
                    genre: 'rock',
                    moods: ['friday-deploy'],
                ),
                new Playlist(
                    id: 'jazz-debugging-01',
                    title: 'Slow jazz for long stack traces',
                    spotifyId: 'zZ9yY8xX7wW6vV5uU4tT3s',
                    genre: 'jazz',
                    moods: ['debugging'],
                ),
            ],
        );
    }

    /** The genres and the moods, and not one playlist. */
    public static function empty(): Catalog
    {
        return new Catalog(version: 1, genres: self::genres(), moods: self::moods(), playlists: []);
    }

    /** @return list<Genre> */
    private static function genres(): array
    {
        return [
            new Genre('rock', 'Rock'),
            new Genre('jazz', 'Jazz'),
            new Genre('lofi', 'Lo-fi'),
        ];
    }

    /** @return list<Mood> */
    private static function moods(): array
    {
        return [
            new Mood('friday-deploy', 'Friday Deploy', '🚀', 'Brave, energetic, a bit risky.'),
            new Mood('debugging', 'Debugging', '🐛', 'Calm and patient, a long investigation.'),
            new Mood('flow-state', 'Flow State', '🎧', 'Deep focus, no distractions.'),
        ];
    }
}
