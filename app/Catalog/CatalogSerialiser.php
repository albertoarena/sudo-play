<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Turns a validated catalog into plain arrays and back, so one can be kept in a
 * single JSON column.
 *
 * The app parses YAML once, when it syncs. Every start after that reads this
 * shape instead, which is why the playlist carries the bare `spotify_id` rather
 * than the web link it arrived as: what is stored is the normalised form, so no
 * URL is ever parsed twice.
 *
 * Reading is strict and all or nothing. The YAML parser is lenient on purpose,
 * because a contributor wrote that file and one bad row should not take the
 * catalog down. This file was written by the app itself, so a row that does not
 * fit means the database is damaged, not that someone made a typo. Nothing is
 * salvaged: the caller sees no catalog and loads the bundled one again.
 */
final class CatalogSerialiser
{
    private const int SUPPORTED_VERSION = 1;

    /**
     * @return array{version: int, genres: list<array{id: string, label: string}>, moods: list<array{id: string, label: string, emoji: string, description: string}>, playlists: list<array{id: string, title: string, spotify_id: string, genre: string, moods: list<string>, curator: string|null}>}
     */
    public function toArray(Catalog $catalog): array
    {
        return [
            'version' => $catalog->version,
            'genres' => array_map(static fn (Genre $genre): array => [
                'id' => $genre->id,
                'label' => $genre->label,
            ], $catalog->genres),
            'moods' => array_map(static fn (Mood $mood): array => [
                'id' => $mood->id,
                'label' => $mood->label,
                'emoji' => $mood->emoji,
                'description' => $mood->description,
            ], $catalog->moods),
            'playlists' => array_map(static fn (Playlist $playlist): array => [
                'id' => $playlist->id,
                'title' => $playlist->title,
                'spotify_id' => $playlist->spotifyId,
                'genre' => $playlist->genre,
                'moods' => $playlist->moods,
                'curator' => $playlist->curator,
            ], $catalog->playlists),
        ];
    }

    /**
     * The catalog this payload holds, or null when it is not one this app wrote.
     */
    public function fromArray(mixed $payload): ?Catalog
    {
        if (! is_array($payload) || $payload === [] || array_is_list($payload)) {
            return null;
        }

        if (($payload['version'] ?? null) !== self::SUPPORTED_VERSION) {
            return null;
        }

        $genres = $this->section($payload['genres'] ?? null, self::genre(...));
        $moods = $this->section($payload['moods'] ?? null, self::mood(...));
        $playlists = $this->section($payload['playlists'] ?? null, self::playlist(...));

        if ($genres === null || $moods === null || $playlists === null) {
            return null;
        }

        return new Catalog(
            version: self::SUPPORTED_VERSION,
            genres: $genres,
            moods: $moods,
            playlists: $playlists,
        );
    }

    /**
     * One section of the payload, read row by row. Null the moment anything does
     * not fit, because a damaged file is not worth salvaging.
     *
     * @template TRow of Genre|Mood|Playlist
     *
     * @param  callable(array<array-key, mixed>): (TRow|null)  $read
     * @return list<TRow>|null
     */
    private function section(mixed $section, callable $read): ?array
    {
        if (! is_array($section) || ! array_is_list($section)) {
            return null;
        }

        $rows = [];

        foreach ($section as $row) {
            if (! is_array($row)) {
                return null;
            }

            $parsed = $read($row);

            if ($parsed === null) {
                return null;
            }

            $rows[] = $parsed;
        }

        return $rows;
    }

    /** @param  array<array-key, mixed>  $row */
    private static function genre(array $row): ?Genre
    {
        $id = self::text($row, 'id');
        $label = self::text($row, 'label');

        if ($id === null || $label === null) {
            return null;
        }

        return new Genre(id: $id, label: $label);
    }

    /** @param  array<array-key, mixed>  $row */
    private static function mood(array $row): ?Mood
    {
        $id = self::text($row, 'id');
        $label = self::text($row, 'label');
        $emoji = $row['emoji'] ?? '';
        $description = $row['description'] ?? '';

        if ($id === null || $label === null || ! is_string($emoji) || ! is_string($description)) {
            return null;
        }

        return new Mood(id: $id, label: $label, emoji: $emoji, description: $description);
    }

    /** @param  array<array-key, mixed>  $row */
    private static function playlist(array $row): ?Playlist
    {
        $id = self::text($row, 'id');
        $title = self::text($row, 'title');
        $spotifyId = self::text($row, 'spotify_id');
        $genre = self::text($row, 'genre');
        $moods = $row['moods'] ?? null;
        $curator = $row['curator'] ?? null;

        if ($id === null || $title === null || $spotifyId === null || $genre === null) {
            return null;
        }

        if (! is_array($moods) || ! array_is_list($moods) || $moods === []) {
            return null;
        }

        foreach ($moods as $mood) {
            if (! is_string($mood)) {
                return null;
            }
        }

        if ($curator !== null && ! is_string($curator)) {
            return null;
        }

        return new Playlist(
            id: $id,
            title: $title,
            spotifyId: $spotifyId,
            genre: $genre,
            moods: $moods,
            curator: $curator,
        );
    }

    /** @param  array<array-key, mixed>  $row */
    private static function text(array $row, string $field): ?string
    {
        $value = $row[$field] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
