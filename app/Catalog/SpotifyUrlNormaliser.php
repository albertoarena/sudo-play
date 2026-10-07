<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Translates between the three ways a Spotify playlist is written:
 * a public web link, the bare playlist id, and the `spotify:` URI.
 *
 * This is the only class in the app that knows what a Spotify link looks like.
 * It makes no network calls and uses no Spotify API.
 */
final class SpotifyUrlNormaliser
{
    /**
     * A public playlist link, with an optional locale segment and an optional
     * query string. Both are allowed by the catalog format and both are dropped.
     */
    private const string URL_PATTERN = '#^https://open\.spotify\.com/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?playlist/([A-Za-z0-9]{22})(?:\?.*)?\z#';

    /**
     * The playlist id inside a public web link, or null when the link is not one.
     */
    public static function playlistIdFromUrl(string $url): ?string
    {
        if (preg_match(self::URL_PATTERN, $url, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function webUrl(string $playlistId): string
    {
        return 'https://open.spotify.com/playlist/'.$playlistId;
    }

    public static function uri(string $playlistId): string
    {
        return 'spotify:playlist:'.$playlistId;
    }
}
