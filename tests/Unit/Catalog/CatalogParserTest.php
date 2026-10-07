<?php

declare(strict_types=1);

use App\Catalog\CatalogParser;
use App\Catalog\CatalogParseResult;

function catalogFixture(string $name): string
{
    $path = dirname(__DIR__, 2).'/fixtures/catalogs/'.$name;

    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException("Missing catalog fixture: {$path}");
    }

    return $contents;
}

function parseFixture(string $name): CatalogParseResult
{
    return (new CatalogParser)->parse(catalogFixture($name));
}

/**
 * True when one message mentions every needle. Messages are asserted by the
 * facts they have to carry, not word for word, so the wording can be improved
 * without rewriting the tests.
 *
 * @param  list<string>  $messages
 */
function oneMessageMentions(array $messages, string ...$needles): bool
{
    foreach ($messages as $message) {
        $mentionsAll = true;

        foreach ($needles as $needle) {
            if (! str_contains($message, $needle)) {
                $mentionsAll = false;

                break;
            }
        }

        if ($mentionsAll) {
            return true;
        }
    }

    return false;
}

it('parses a valid catalog', function (): void {
    $result = parseFixture('valid.yaml');

    expect($result->errors)->toBe([])
        ->and($result->warnings)->toBe([])
        ->and($result->catalog)->not->toBeNull();

    $catalog = $result->catalog;

    expect($catalog->version)->toBe(1)
        ->and($catalog->genres)->toHaveCount(2)
        ->and($catalog->genres[0]->id)->toBe('rock')
        ->and($catalog->genres[0]->label)->toBe('Rock')
        ->and($catalog->moods)->toHaveCount(2)
        ->and($catalog->moods[0]->id)->toBe('friday-deploy')
        ->and($catalog->moods[0]->label)->toBe('Friday Deploy')
        ->and($catalog->moods[0]->emoji)->toBe('🚀')
        ->and($catalog->moods[0]->description)->toBe('Shipping to production on a Friday. Be brave.')
        ->and($catalog->playlists)->toHaveCount(2);

    expect($catalog->playlists[0])
        ->id->toBe('rock-friday-deploy-01')
        ->title->toBe('Riffs for risky releases')
        ->spotifyId->toBe('aB1cD2eF3gH4iJ5kL6mN7o')
        ->genre->toBe('rock')
        ->moods->toBe(['friday-deploy', 'debugging'])
        ->curator->toBe('octocat');
});

it('leaves out a curator that the catalog does not give', function (): void {
    $catalog = parseFixture('valid.yaml')->catalog;

    expect($catalog->playlists[1]->curator)->toBeNull();
});

it('strips the locale segment and the query string from a url', function (): void {
    $catalog = parseFixture('valid.yaml')->catalog;

    expect($catalog->playlists[1])
        ->spotifyId->toBe('zZ9yY8xX7wW6vV5uU4tT3s')
        ->and($catalog->playlists[1]->webUrl())->toBe('https://open.spotify.com/playlist/zZ9yY8xX7wW6vV5uU4tT3s')
        ->and($catalog->playlists[1]->spotifyUri())->toBe('spotify:playlist:zZ9yY8xX7wW6vV5uU4tT3s');
});

it('rejects broken yaml as a whole', function (): void {
    $result = parseFixture('broken-yaml.yaml');

    expect($result->catalog)->toBeNull()
        ->and($result->errors)->not->toBe([])
        ->and(oneMessageMentions($result->errors, 'YAML'))->toBeTrue();
});

it('rejects the wrong version as a whole', function (): void {
    $result = parseFixture('wrong-version.yaml');

    expect($result->catalog)->toBeNull()
        ->and(oneMessageMentions($result->errors, 'version'))->toBeTrue();
});

it('rejects a missing version as a whole', function (): void {
    $result = parseFixture('missing-version.yaml');

    expect($result->catalog)->toBeNull()
        ->and(oneMessageMentions($result->errors, 'version'))->toBeTrue();
});

it('rejects a file that is not a catalog', function (string $yaml): void {
    $result = (new CatalogParser)->parse($yaml);

    expect($result->catalog)->toBeNull()
        ->and($result->errors)->not->toBe([]);
})->with([
    'empty' => '',
    'only a comment' => '# nothing here',
    'a plain string' => 'just some text',
    'a list' => "- one\n- two",
]);

it('skips a playlist with an unknown genre and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'pop-flow-state-01', 'genre', 'pop'))->toBeTrue();
});

it('skips a playlist with an unknown mood and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-shipped-it-01', 'mood', 'shipped-it'))->toBeTrue();
});

it('skips a playlist with no moods and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-no-moods-01', 'mood'))->toBeTrue();
});

it('skips a playlist whose url is not a spotify playlist link and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-bad-url-01', 'url'))->toBeTrue();
});

it('skips a playlist whose spotify id is not 22 letters and digits and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-short-id-01', 'url'))->toBeTrue();
});

it('skips a duplicate playlist id and keeps the first one', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-flow-state-01', 'duplicate'))->toBeTrue()
        ->and($result->catalog->playlists[0]->title)->toBe('The only good one');
});

it('skips a playlist id that is not a lowercase slug and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'Rock_Flow_State', 'id'))->toBeTrue();
});

it('skips a playlist with no id and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'playlist', 'id', 'missing'))->toBeTrue();
});

it('skips a playlist with no title and says why', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect(oneMessageMentions($result->errors, 'rock-no-title-01', 'title'))->toBeTrue();
});

it('keeps the valid playlists and drops the rest', function (): void {
    $result = parseFixture('invalid-playlists.yaml');

    expect($result->catalog)->not->toBeNull()
        ->and($result->catalog->playlists)->toHaveCount(1)
        ->and($result->catalog->playlists[0]->id)->toBe('rock-flow-state-01')
        ->and($result->errors)->toHaveCount(9);
});

it('keeps a playlist that carries unknown fields and reports them as warnings', function (): void {
    $result = parseFixture('unknown-field.yaml');

    expect($result->errors)->toBe([])
        ->and($result->catalog->playlists)->toHaveCount(1)
        ->and($result->catalog->playlists[0]->id)->toBe('rock-flow-state-01')
        ->and($result->warnings)->toHaveCount(2)
        ->and(oneMessageMentions($result->warnings, 'rock-flow-state-01', 'tier'))->toBeTrue()
        ->and(oneMessageMentions($result->warnings, 'rock-flow-state-01', 'source'))->toBeTrue();
});

it('parses a catalog that has no playlists', function (): void {
    $result = parseFixture('no-playlists.yaml');

    expect($result->errors)->toBe([])
        ->and($result->catalog)->not->toBeNull()
        ->and($result->catalog->playlists)->toBe([])
        ->and($result->catalog->genres)->toHaveCount(1)
        ->and($result->catalog->moods)->toHaveCount(1);
});

it('skips a duplicate or malformed genre and mood, and keeps the good ones', function (): void {
    $result = parseFixture('invalid-definitions.yaml');

    expect($result->catalog)->not->toBeNull()
        ->and($result->catalog->genres)->toHaveCount(1)
        ->and($result->catalog->genres[0]->id)->toBe('rock')
        ->and($result->catalog->moods)->toHaveCount(1)
        ->and($result->catalog->moods[0]->id)->toBe('flow-state')
        ->and(oneMessageMentions($result->errors, 'genre', 'rock', 'duplicate'))->toBeTrue()
        ->and(oneMessageMentions($result->errors, 'genre', 'Classical'))->toBeTrue()
        ->and(oneMessageMentions($result->errors, 'mood', 'flow-state', 'duplicate'))->toBeTrue()
        ->and(oneMessageMentions($result->errors, 'mood', 'debugging', 'label'))->toBeTrue();
});

it('keeps the playlist whose genre and mood survived validation', function (): void {
    $result = parseFixture('invalid-definitions.yaml');

    expect($result->catalog->playlists)->toHaveCount(1)
        ->and($result->catalog->playlists[0]->id)->toBe('rock-flow-state-01');
});

it('rejects a catalog whose genres, moods or playlists are not lists', function (string $yaml): void {
    $result = (new CatalogParser)->parse($yaml);

    expect($result->catalog)->toBeNull()
        ->and($result->errors)->not->toBe([]);
})->with([
    'genres is a string' => "version: 1\ngenres: rock\nmoods: []\nplaylists: []",
    'moods is a string' => "version: 1\ngenres: []\nmoods: calm\nplaylists: []",
    'playlists is a string' => "version: 1\ngenres: []\nmoods: []\nplaylists: everything",
]);

it('treats missing genres, moods and playlists keys as empty', function (): void {
    $result = (new CatalogParser)->parse('version: 1');

    expect($result->errors)->toBe([])
        ->and($result->catalog)->not->toBeNull()
        ->and($result->catalog->genres)->toBe([])
        ->and($result->catalog->moods)->toBe([])
        ->and($result->catalog->playlists)->toBe([]);
});
