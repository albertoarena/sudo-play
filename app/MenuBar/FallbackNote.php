<?php

declare(strict_types=1);

namespace App\MenuBar;

use App\Catalog\Catalog;
use App\Rotation\Pick;
use App\Rotation\PickSource;

/**
 * The one line the window shows when the pick had to come from a wider search.
 * Requirements 5: "No rock playlist for Debugging yet, here is a jazz one. Add
 * one on GitHub!"
 *
 * Genres read lower case in the middle of a sentence, moods keep their label,
 * because a mood label is a name ("Friday Deploy") and a genre is a word.
 */
final class FallbackNote
{
    private const string INVITATION = 'Add one on GitHub!';

    /**
     * @param  string  $shownMood  the label of the mood the card shows for this pick
     */
    public static function for(Pick $pick, Catalog $catalog, string $shownMood): ?string
    {
        $asked = sprintf(
            'No %s playlist for %s yet',
            self::genreWord($catalog, $pick->genre),
            self::moodLabel($catalog, $pick->mood),
        );

        return match ($pick->source) {
            PickSource::Exact => null,
            PickSource::SameMoodAnyGenre => sprintf(
                '%s, here is a %s one. %s',
                $asked,
                self::genreWord($catalog, $pick->playlist->genre),
                self::INVITATION,
            ),
            PickSource::SameGenreAnyMood => sprintf(
                '%s, here is a %s one for %s. %s',
                $asked,
                self::genreWord($catalog, $pick->playlist->genre),
                $shownMood,
                self::INVITATION,
            ),
        };
    }

    private static function genreWord(Catalog $catalog, string $id): string
    {
        foreach ($catalog->genres as $genre) {
            if ($genre->id === $id) {
                return mb_strtolower($genre->label);
            }
        }

        return $id;
    }

    private static function moodLabel(Catalog $catalog, string $id): string
    {
        foreach ($catalog->moods as $mood) {
            if ($mood->id === $id) {
                return $mood->label;
            }
        }

        return $id;
    }
}
