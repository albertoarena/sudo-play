<?php

declare(strict_types=1);

namespace App\Rotation;

use App\Catalog\Playlist;
use InvalidArgumentException;

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
    public function __construct(
        private RandomSource $random,
    ) {}

    /**
     * @param  list<Playlist>  $playlists  the whole catalog, in any order
     */
    public function pick(CalendarDate $date, string $genre, string $mood, array $playlists): ?Pick
    {
        return $this->at($date, $genre, $mood, $playlists, 0);
    }

    /**
     * Where the Next button lands. Requirements 5.
     *
     * Next walks on from today's pick through the other candidates, in the
     * same sorted order, and wraps at the end. $presses is how many times the
     * button has been pressed, which the caller keeps in memory: the position
     * is not stored anywhere, so it starts again at today's pick when the app
     * restarts or the local date changes.
     *
     * @param  list<Playlist>  $playlists
     *
     * @throws InvalidArgumentException when $presses is negative
     */
    public function next(CalendarDate $date, string $genre, string $mood, array $playlists, int $presses): ?Pick
    {
        if ($presses < 0) {
            throw new InvalidArgumentException('Next only goes forwards, so the number of presses cannot be negative.');
        }

        return $this->at($date, $genre, $mood, $playlists, $presses);
    }

    /**
     * A pick for a genre and mood chosen at random, from the pairs the catalog
     * actually covers. Requirements 5.
     *
     * The pairs come from the playlists themselves, so every pair offered has
     * at least one playlist behind it and the result never needs a fallback.
     * Only the pair is random: once it is chosen, the playlist is the one the
     * day gives, the same as if the user had picked that pair by hand.
     *
     * @param  list<Playlist>  $playlists
     */
    public function surprise(CalendarDate $date, array $playlists): ?Pick
    {
        $pairs = $this->pairs($playlists);

        if ($pairs === []) {
            return null;
        }

        [$genre, $mood] = $pairs[$this->random->indexBelow(count($pairs))];

        return $this->pick($date, $genre, $mood, $playlists);
    }

    /**
     * The candidate $presses places on from where the day landed.
     *
     * @param  list<Playlist>  $playlists
     * @param  int  $presses  0 or more
     */
    private function at(CalendarDate $date, string $genre, string $mood, array $playlists, int $presses): ?Pick
    {
        $resolved = $this->resolve($playlists, $genre, $mood);

        if ($resolved === null) {
            return null;
        }

        [$candidates, $source] = $resolved;

        $count = count($candidates);
        $index = (DailyRotation::indexFor($date, $genre, $mood, $count) + $presses) % $count;

        return new Pick($candidates[$index], $genre, $mood, $source);
    }

    /**
     * The list the pick is made from, and where it came from.
     *
     * The chosen pair comes first. When the catalog has nothing for it the
     * search widens, in the order set by requirements 5: the same mood in any
     * genre, then the same genre in any mood. Null means even that found
     * nothing, and the window shows its empty state.
     *
     * The day, the genre and the mood that go into the rotation are the ones
     * the user asked for, whichever list is used, so a fallback pick is as
     * steady and as shareable as any other.
     *
     * @param  list<Playlist>  $playlists
     * @return array{list<Playlist>, PickSource}|null
     */
    private function resolve(array $playlists, string $genre, string $mood): ?array
    {
        $searches = [
            [PickSource::Exact, $genre, $mood],
            [PickSource::SameMoodAnyGenre, null, $mood],
            [PickSource::SameGenreAnyMood, $genre, null],
        ];

        foreach ($searches as [$source, $byGenre, $byMood]) {
            $candidates = $this->candidates($playlists, $byGenre, $byMood);

            if ($candidates !== []) {
                return [$candidates, $source];
            }
        }

        return null;
    }

    /**
     * Every genre and mood pair the catalog has a playlist for, sorted, so the
     * same catalog always offers the same choices in the same order.
     *
     * @param  list<Playlist>  $playlists
     * @return list<array{string, string}>
     */
    private function pairs(array $playlists): array
    {
        $pairs = [];

        foreach ($playlists as $playlist) {
            foreach ($playlist->moods as $mood) {
                $pairs[$playlist->genre.'|'.$mood] = [$playlist->genre, $mood];
            }
        }

        ksort($pairs, SORT_STRING);

        return array_values($pairs);
    }

    /**
     * The playlists of that genre carrying that mood, sorted by id in byte
     * order so every copy of the app agrees on the order.
     *
     * A null genre or mood means "any", which is how the fallback searches
     * widen one side at a time.
     *
     * @param  list<Playlist>  $playlists
     * @return list<Playlist>
     */
    private function candidates(array $playlists, ?string $genre, ?string $mood): array
    {
        $candidates = array_values(array_filter(
            $playlists,
            fn (Playlist $playlist): bool => ($genre === null || $playlist->genre === $genre)
                && ($mood === null || in_array($mood, $playlist->moods, true)),
        ));

        usort($candidates, fn (Playlist $a, Playlist $b): int => strcmp($a->id, $b->id));

        return $candidates;
    }
}
