<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * The extra checks a catalog has to pass before it ships, from requirements
 * 10.1. These are release policy rather than catalog validity: a catalog with
 * no playlists is a normal development state, and the app handles it, but it
 * is not something to tag and hand to people.
 *
 * Only `catalog:validate --release` and the release workflow run this.
 */
final class ReleaseGate
{
    private const string PLACEHOLDER = 'TODO';

    /**
     * Everything standing between this catalog and a release. Empty means ready.
     *
     * @return list<string>
     */
    public static function problems(Catalog $catalog): array
    {
        $problems = [];

        if ($catalog->playlists === []) {
            $problems[] = 'The catalog has no playlists. A release needs at least one, so the app plays something on first run.';
        }

        foreach (self::textFields($catalog) as [$where, $field, $value]) {
            if (str_contains($value, self::PLACEHOLDER)) {
                $problems[] = sprintf('%s: the %s still says %s.', $where, $field, self::PLACEHOLDER);
            }
        }

        return $problems;
    }

    /**
     * Every field a maintainer could leave a placeholder in, with the row and
     * the field name to report it by.
     *
     * @return list<array{string, string, string}>
     */
    private static function textFields(Catalog $catalog): array
    {
        $fields = [];

        foreach ($catalog->genres as $genre) {
            $where = sprintf('genre "%s"', $genre->id);

            $fields[] = [$where, 'id', $genre->id];
            $fields[] = [$where, 'label', $genre->label];
        }

        foreach ($catalog->moods as $mood) {
            $where = sprintf('mood "%s"', $mood->id);

            $fields[] = [$where, 'id', $mood->id];
            $fields[] = [$where, 'label', $mood->label];
            $fields[] = [$where, 'emoji', $mood->emoji];
            $fields[] = [$where, 'description', $mood->description];
        }

        foreach ($catalog->playlists as $playlist) {
            $where = sprintf('playlist "%s"', $playlist->id);

            $fields[] = [$where, 'id', $playlist->id];
            $fields[] = [$where, 'title', $playlist->title];
            $fields[] = [$where, 'url', $playlist->spotifyId];
            $fields[] = [$where, 'curator', $playlist->curator ?? ''];
        }

        return $fields;
    }
}
