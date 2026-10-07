<?php

declare(strict_types=1);

namespace App\Catalog;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Turns a catalog YAML file into a Catalog plus the list of problems found.
 *
 * Two kinds of problem, because they need different answers:
 *
 * - The whole file is wrong (bad YAML, a version this app does not read).
 *   No catalog comes back, so the caller keeps the one it already had.
 * - One row is wrong. That row is dropped and the rest still loads, so one
 *   bad pull request cannot take the catalog down for everybody.
 *
 * Takes a string rather than a path, so it needs no filesystem and no
 * framework. The caller reads the file.
 */
final class CatalogParser
{
    private const int SUPPORTED_VERSION = 1;

    /** Lowercase slug: letters and digits, with single hyphens between them. */
    private const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const array SECTIONS = ['genres', 'moods', 'playlists'];

    /** Anything else on a playlist is ignored and reported, never a reason to skip it. */
    private const array PLAYLIST_FIELDS = ['id', 'title', 'url', 'genre', 'moods', 'curator'];

    public function parse(string $yaml): CatalogParseResult
    {
        try {
            $raw = Yaml::parse($yaml);
        } catch (ParseException $exception) {
            return self::rejected('The file is not valid YAML: '.$exception->getMessage());
        }

        if (! is_array($raw) || $raw === [] || array_is_list($raw)) {
            return self::rejected('The catalog must be a mapping, with a version and the genres, moods and playlists below it.');
        }

        $version = $raw['version'] ?? null;

        if ($version !== self::SUPPORTED_VERSION) {
            return self::rejected(sprintf(
                'The catalog version must be %d, found %s. The whole file is ignored, because a format this app does not know cannot be read in part.',
                self::SUPPORTED_VERSION,
                self::describe($version),
            ));
        }

        $sections = [];

        foreach (self::SECTIONS as $name) {
            $section = $raw[$name] ?? [];

            if (! is_array($section) || ! array_is_list($section)) {
                return self::rejected(sprintf('The %s section must be a list.', $name));
            }

            $sections[$name] = $section;
        }

        $errors = [];
        $warnings = [];

        $genres = $this->parseGenres($sections['genres'], $errors);
        $moods = $this->parseMoods($sections['moods'], $errors);

        $playlists = $this->parsePlaylists(
            $sections['playlists'],
            array_map(static fn (Genre $genre): string => $genre->id, $genres),
            array_map(static fn (Mood $mood): string => $mood->id, $moods),
            $errors,
            $warnings,
        );

        return new CatalogParseResult(
            catalog: new Catalog(
                version: self::SUPPORTED_VERSION,
                genres: $genres,
                moods: $moods,
                playlists: $playlists,
            ),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * @param  list<mixed>  $rows
     * @param  list<string>  $errors
     * @return list<Genre>
     */
    private function parseGenres(array $rows, array &$errors): array
    {
        $genres = [];
        $seen = [];

        foreach ($this->mappings($rows, 'genre', $errors) as [$position, $row]) {
            $id = $this->validId($row, 'genre', $position, $seen, $errors);

            if ($id === null) {
                continue;
            }

            $label = $row['label'] ?? null;

            if (! is_string($label) || $label === '') {
                $errors[] = sprintf('genre "%s": label is missing.', $id);

                continue;
            }

            $seen[$id] = true;
            $genres[] = new Genre(id: $id, label: $label);
        }

        return $genres;
    }

    /**
     * @param  list<mixed>  $rows
     * @param  list<string>  $errors
     * @return list<Mood>
     */
    private function parseMoods(array $rows, array &$errors): array
    {
        $moods = [];
        $seen = [];

        foreach ($this->mappings($rows, 'mood', $errors) as [$position, $row]) {
            $id = $this->validId($row, 'mood', $position, $seen, $errors);

            if ($id === null) {
                continue;
            }

            $label = $row['label'] ?? null;

            if (! is_string($label) || $label === '') {
                $errors[] = sprintf('mood "%s": label is missing.', $id);

                continue;
            }

            $emoji = $row['emoji'] ?? null;
            $description = $row['description'] ?? null;

            $seen[$id] = true;
            $moods[] = new Mood(
                id: $id,
                label: $label,
                emoji: is_string($emoji) ? $emoji : '',
                description: is_string($description) ? $description : '',
            );
        }

        return $moods;
    }

    /**
     * @param  list<mixed>  $rows
     * @param  list<string>  $genreIds
     * @param  list<string>  $moodIds
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return list<Playlist>
     */
    private function parsePlaylists(array $rows, array $genreIds, array $moodIds, array &$errors, array &$warnings): array
    {
        $playlists = [];
        $seen = [];

        foreach ($this->mappings($rows, 'playlist', $errors) as [$position, $row]) {
            $id = $this->validId($row, 'playlist', $position, $seen, $errors);

            if ($id === null) {
                continue;
            }

            $where = sprintf('playlist "%s"', $id);

            $title = $row['title'] ?? null;

            if (! is_string($title) || $title === '') {
                $errors[] = $where.': title is missing.';

                continue;
            }

            $url = $row['url'] ?? null;

            if (! is_string($url) || $url === '') {
                $errors[] = $where.': url is missing.';

                continue;
            }

            $spotifyId = SpotifyUrlNormaliser::playlistIdFromUrl($url);

            if ($spotifyId === null) {
                $errors[] = $where.': url must be a public Spotify playlist link, https://open.spotify.com/playlist/ followed by a 22 character id.';

                continue;
            }

            $genre = $row['genre'] ?? null;

            if (! is_string($genre) || $genre === '') {
                $errors[] = $where.': genre is missing.';

                continue;
            }

            if (! in_array($genre, $genreIds, true)) {
                $errors[] = sprintf('%s: unknown genre "%s". Add it to the genres section first.', $where, $genre);

                continue;
            }

            $moods = $this->validMoods($row['moods'] ?? null, $moodIds, $where, $errors);

            if ($moods === null) {
                continue;
            }

            foreach (array_keys($row) as $field) {
                if (! in_array($field, self::PLAYLIST_FIELDS, true)) {
                    $warnings[] = sprintf('%s: unknown field "%s" ignored.', $where, (string) $field);
                }
            }

            $curator = $row['curator'] ?? null;

            $seen[$id] = true;
            $playlists[] = new Playlist(
                id: $id,
                title: $title,
                spotifyId: $spotifyId,
                genre: $genre,
                moods: $moods,
                curator: is_string($curator) && $curator !== '' ? $curator : null,
            );
        }

        return $playlists;
    }

    /**
     * The mood ids on one playlist, or null when the playlist has to be skipped.
     *
     * @param  list<string>  $moodIds
     * @param  list<string>  $errors
     * @return list<string>|null
     */
    private function validMoods(mixed $moods, array $moodIds, string $where, array &$errors): ?array
    {
        if (! is_array($moods) || ! array_is_list($moods)) {
            $errors[] = $where.': moods must be a list of mood ids.';

            return null;
        }

        if ($moods === []) {
            $errors[] = $where.': moods must name at least one mood.';

            return null;
        }

        $known = [];

        foreach ($moods as $mood) {
            if (! is_string($mood) || ! in_array($mood, $moodIds, true)) {
                $errors[] = sprintf('%s: unknown mood "%s". Add it to the moods section first.', $where, self::describe($mood));

                return null;
            }

            $known[] = $mood;
        }

        return $known;
    }

    /**
     * Keeps the rows that are mappings, reports the rest, and hands back each
     * surviving row with the position it had in the file.
     *
     * @param  list<mixed>  $rows
     * @param  list<string>  $errors
     * @return list<array{int, array<array-key, mixed>}>
     */
    private function mappings(array $rows, string $kind, array &$errors): array
    {
        $mappings = [];

        foreach ($rows as $position => $row) {
            if (! is_array($row) || $row === [] || array_is_list($row)) {
                $errors[] = sprintf('%s #%d: each %s must be a mapping of fields.', $kind, $position + 1, $kind);

                continue;
            }

            $mappings[] = [$position, $row];
        }

        return $mappings;
    }

    /**
     * The first half of every row, shared by all three sections: an id that is
     * a lowercase slug and has not been used yet. Null means skip the row.
     *
     * @param  array<array-key, mixed>  $row
     * @param  array<string, true>  $seen
     * @param  list<string>  $errors
     */
    private function validId(array $row, string $kind, int $position, array $seen, array &$errors): ?string
    {
        $id = $row['id'] ?? null;

        if (! is_string($id) || $id === '') {
            $errors[] = sprintf('%s #%d: id is missing.', $kind, $position + 1);

            return null;
        }

        if (preg_match(self::SLUG_PATTERN, $id) !== 1) {
            $errors[] = sprintf('%s "%s": id must be a lowercase slug, letters and digits with single hyphens, like "friday-deploy".', $kind, $id);

            return null;
        }

        if (isset($seen[$id])) {
            $errors[] = sprintf('%s "%s": duplicate %s id, the first one is kept.', $kind, $id, $kind);

            return null;
        }

        return $id;
    }

    private static function rejected(string $message): CatalogParseResult
    {
        return new CatalogParseResult(catalog: null, errors: [$message]);
    }

    private static function describe(mixed $value): string
    {
        if ($value === null) {
            return 'nothing';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return var_export($value, true);
        }

        return get_debug_type($value);
    }
}
