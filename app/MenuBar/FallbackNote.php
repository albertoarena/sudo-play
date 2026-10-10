<?php

declare(strict_types=1);

namespace App\MenuBar;

use App\Rotation\Pick;
use App\Rotation\PickSource;

/**
 * The comment the command line shows when the pick had to come from a wider
 * search. Requirements 5: "no rock for debugging, playing jazz".
 *
 * It uses the ids as typed in the command, not the labels, because it reads
 * as a line in the same terminal. The window draws the hash sign.
 */
final class FallbackNote
{
    /**
     * @param  string  $shownMood  the id of the mood the card shows for this pick
     */
    public static function for(Pick $pick, string $shownMood): ?string
    {
        $asked = sprintf('no %s for %s', $pick->genre, $pick->mood);

        return match ($pick->source) {
            PickSource::Exact => null,
            PickSource::SameMoodAnyGenre => sprintf('%s, playing %s', $asked, $pick->playlist->genre),
            PickSource::SameGenreAnyMood => sprintf(
                '%s, playing a %s pick for %s',
                $asked,
                $pick->playlist->genre,
                $shownMood,
            ),
        };
    }
}
