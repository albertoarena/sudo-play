<?php

declare(strict_types=1);

namespace App\Rotation;

/**
 * Which candidate list the pick came from.
 *
 * The UI needs this to explain itself. When there is no playlist for the
 * chosen pair, the rotation widens the search in the order set by
 * requirements 5, and the window says so: "No rock playlist for Debugging
 * yet, here is a jazz one."
 */
enum PickSource: string
{
    /** Playlists of the chosen genre that carry the chosen mood. */
    case Exact = 'exact';

    /** First fallback: the chosen mood, any genre. */
    case SameMoodAnyGenre = 'same-mood-any-genre';

    /** Second fallback: the chosen genre, any mood. */
    case SameGenreAnyMood = 'same-genre-any-mood';
}
