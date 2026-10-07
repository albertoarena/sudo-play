<?php

declare(strict_types=1);

use App\Catalog\SpotifyUrlNormaliser;

const VALID_ID = 'aB1cD2eF3gH4iJ5kL6mN7o';

it('reads the playlist id from a plain web url', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/playlist/'.VALID_ID))
        ->toBe(VALID_ID);
});

it('strips a query string', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/playlist/'.VALID_ID.'?si=abc123&utm_source=copy-link'))
        ->toBe(VALID_ID);
});

it('strips the locale segment', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/intl-it/playlist/'.VALID_ID.'?si=abc'))
        ->toBe(VALID_ID);
});

it('strips a locale segment that carries a region', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/intl-pt-br/playlist/'.VALID_ID))
        ->toBe(VALID_ID);
});

it('rejects a url that is not https', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('http://open.spotify.com/playlist/'.VALID_ID))
        ->toBeNull();
});

it('rejects another host', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.example.com/playlist/'.VALID_ID))
        ->toBeNull();
});

it('rejects a link that is not a playlist', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/album/'.VALID_ID))
        ->toBeNull();
});

it('rejects an id that is not 22 characters', function (string $id): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/playlist/'.$id))
        ->toBeNull();
})->with([
    'too short' => 'aB1cD2eF3gH4iJ5kL6mN7',
    'too long' => 'aB1cD2eF3gH4iJ5kL6mN7oP',
    'empty' => '',
]);

it('rejects an id that is not letters and digits', function (string $id): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/playlist/'.$id))
        ->toBeNull();
})->with([
    'hyphen' => 'aB1cD2eF3gH4iJ5kL6mN7-',
    'underscore' => 'aB1cD2eF3gH4iJ5kL6mN7_',
    'slash' => 'aB1cD2eF3gH4iJ5kL6mN7/',
    'accent' => 'aB1cD2eF3gH4iJ5kL6mN7é',
]);

it('rejects a url with an extra path segment', function (): void {
    expect(SpotifyUrlNormaliser::playlistIdFromUrl('https://open.spotify.com/playlist/'.VALID_ID.'/tracks'))
        ->toBeNull();
});

it('builds the web url from an id', function (): void {
    expect(SpotifyUrlNormaliser::webUrl(VALID_ID))
        ->toBe('https://open.spotify.com/playlist/'.VALID_ID);
});

it('builds the spotify uri from an id', function (): void {
    expect(SpotifyUrlNormaliser::uri(VALID_ID))
        ->toBe('spotify:playlist:'.VALID_ID);
});

it('converts a url to a uri and back', function (): void {
    $url = 'https://open.spotify.com/intl-it/playlist/'.VALID_ID.'?si=abc';

    $id = SpotifyUrlNormaliser::playlistIdFromUrl($url);

    expect($id)->not->toBeNull()
        ->and(SpotifyUrlNormaliser::uri($id))->toBe('spotify:playlist:'.VALID_ID)
        ->and(SpotifyUrlNormaliser::webUrl($id))->toBe('https://open.spotify.com/playlist/'.VALID_ID);
});
