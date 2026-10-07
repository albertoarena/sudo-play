<?php

declare(strict_types=1);

namespace App\Rotation;

use App\Catalog\Playlist;

/**
 * Today's playlist, from a day, a genre and a mood. Requirements 5.
 *
 * This class decides which playlists are in the running and in what order.
 * Where the day lands in that list is `DailyRotation`.
 *
 * The pick is a pure function of what it is given, so two people with the
 * same catalog see the same playlist and can share "today's pick". Nothing is
 * stored, and the day comes in as a `CalendarDate`, so nothing here reads a
 * clock either.
 */
final readonly class DailyPicker
{
    /**
     * @param  list<Playlist>  $playlists  the whole catalog, in any order
     */
    public function pick(CalendarDate $date, string $genre, string $mood, array $playlists): ?Pick
    {
        $candidates = $this->candidates($playlists, $genre, $mood);

        if ($candidates === []) {
            return null;
        }

        $index = DailyRotation::indexFor($date, $genre, $mood, count($candidates));

        return new Pick($candidates[$index], $genre, $mood, PickSource::Exact);
    }

    /**
     * The playlists of that genre carrying that mood, sorted by id in byte
     * order so every copy of the app agrees on the order.
     *
     * @param  list<Playlist>  $playlists
     * @return list<Playlist>
     */
    private function candidates(array $playlists, string $genre, string $mood): array
    {
        $candidates = array_values(array_filter(
            $playlists,
            fn (Playlist $playlist): bool => $playlist->genre === $genre
                && in_array($mood, $playlist->moods, true),
        ));

        usort($candidates, fn (Playlist $a, Playlist $b): int => strcmp($a->id, $b->id));

        return $candidates;
    }
}
