<?php

declare(strict_types=1);

use App\Catalog\Playlist;
use App\Rotation\CalendarDate;
use App\Rotation\DailyPicker;
use App\Rotation\PickSource;
use Tests\Support\CalendarDays;
use Tests\Support\FixedRandomSource;

/**
 * @param  list<string>  $moods
 */
function rotationPlaylist(string $id, string $genre, array $moods): Playlist
{
    return new Playlist(
        id: $id,
        title: 'Playlist '.$id,
        spotifyId: substr(md5($id), 0, 22),
        genre: $genre,
        moods: $moods,
    );
}

/**
 * Three rock playlists that fit flow-state, already in id order, so a test can
 * name the one it expects by index.
 *
 * @return list<Playlist>
 */
function rotationCandidates(): array
{
    return [
        rotationPlaylist('rock-flow-01', 'rock', ['flow-state']),
        rotationPlaylist('rock-flow-02', 'rock', ['flow-state', 'debugging']),
        rotationPlaylist('rock-flow-03', 'rock', ['flow-state']),
    ];
}

/**
 * A catalog that spreads over several genres and moods, where two playlists
 * fit more than one mood.
 *
 * The pairs it offers, in the order the picker sorts them: jazz|debugging,
 * lofi|coffee-needed, lofi|flow-state, rock|debugging, rock|flow-state.
 *
 * @return list<Playlist>
 */
function rotationMixedCatalog(): array
{
    return [
        rotationPlaylist('rock-flow-01', 'rock', ['flow-state']),
        rotationPlaylist('rock-flow-02', 'rock', ['flow-state', 'debugging']),
        rotationPlaylist('jazz-debug-01', 'jazz', ['debugging']),
        rotationPlaylist('lofi-coffee-01', 'lofi', ['coffee-needed', 'flow-state']),
    ];
}

function rotationPicker(int $randomIndex = 0): DailyPicker
{
    return new DailyPicker(new FixedRandomSource($randomIndex));
}

it('returns the same playlist for the same date, genre and mood', function (): void {
    $date = CalendarDate::fromString('2026-01-05');

    $first = rotationPicker()->pick($date, 'rock', 'flow-state', rotationCandidates());
    $second = rotationPicker()->pick($date, 'rock', 'flow-state', rotationCandidates());

    expect($first?->playlist->id)->not->toBeNull()
        ->and($first?->playlist->id)->toBe($second?->playlist->id);
});

it('can return a different playlist on a different date', function (): void {
    $playlists = rotationCandidates();
    $picker = rotationPicker();

    $fifth = $picker->pick(CalendarDate::fromString('2026-01-05'), 'rock', 'flow-state', $playlists);
    $sixth = $picker->pick(CalendarDate::fromString('2026-01-06'), 'rock', 'flow-state', $playlists);

    expect($fifth?->playlist->id)->not->toBe($sixth?->playlist->id);
});

it('says the pick is an exact match and carries the genre and mood it was asked for', function (): void {
    $pick = rotationPicker()->pick(
        CalendarDate::fromString('2026-01-05'),
        'rock',
        'flow-state',
        rotationCandidates(),
    );

    expect($pick?->source)->toBe(PickSource::Exact)
        ->and($pick?->isFallback())->toBeFalse()
        ->and($pick?->genre)->toBe('rock')
        ->and($pick?->mood)->toBe('flow-state');
});

it('only considers playlists of that genre that carry that mood', function (): void {
    $playlists = [
        ...rotationCandidates(),
        rotationPlaylist('jazz-flow-01', 'jazz', ['flow-state']),
        rotationPlaylist('rock-monday-01', 'rock', ['blue-monday']),
    ];

    $wanted = ['rock-flow-01', 'rock-flow-02', 'rock-flow-03'];

    foreach (CalendarDays::run('2026-01-01', 40) as $date) {
        $pick = rotationPicker()->pick($date, 'rock', 'flow-state', $playlists);

        expect($pick?->playlist->id)->toBeIn($wanted);
    }
});

it('does not depend on the order the playlists are listed in', function (): void {
    $date = CalendarDate::fromString('2026-01-05');
    $listed = rotationCandidates();

    $inOrder = rotationPicker()->pick($date, 'rock', 'flow-state', $listed);
    $reversed = rotationPicker()->pick($date, 'rock', 'flow-state', array_reverse($listed));

    expect($inOrder?->playlist->id)->toBe($reversed?->playlist->id);
});

it('never returns the same playlist two days running', function (int $size): void {
    $playlists = [];

    for ($number = 1; $number <= $size; $number++) {
        $playlists[] = rotationPlaylist(sprintf('rock-flow-%02d', $number), 'rock', ['flow-state']);
    }

    $picker = rotationPicker();
    $previous = null;

    foreach (CalendarDays::run('2026-01-01', 400) as $date) {
        $pick = $picker->pick($date, 'rock', 'flow-state', $playlists);

        if ($previous !== null) {
            expect($pick?->playlist->id)->not->toBe($previous, 'repeated on '.$date->value);
        }

        $previous = $pick?->playlist->id;
    }
})->with([2, 3, 4, 5, 8, 13]);

it('returns the only candidate every day', function (): void {
    $playlists = [rotationPlaylist('rock-flow-01', 'rock', ['flow-state'])];
    $picker = rotationPicker();

    foreach (CalendarDays::run('2026-01-01', 30) as $date) {
        $pick = $picker->pick($date, 'rock', 'flow-state', $playlists);

        expect($pick?->playlist->id)->toBe('rock-flow-01');
    }
});

it('surprises with a pair that has at least one playlist', function (int $choice): void {
    $playlists = rotationMixedCatalog();

    $pick = rotationPicker($choice)->surprise(CalendarDate::fromString('2026-01-05'), $playlists);

    expect($pick)->not->toBeNull()
        ->and($pick?->playlist->genre)->toBe($pick?->genre)
        ->and($pick?->mood)->toBeIn($pick?->playlist->moods ?? [])
        ->and($pick?->source)->toBe(PickSource::Exact);
})->with([0, 1, 2, 3, 4]);

it('offers every pair the catalog covers, and nothing else', function (): void {
    $playlists = rotationMixedCatalog();
    $random = new FixedRandomSource;
    $picker = new DailyPicker($random);

    $picker->surprise(CalendarDate::fromString('2026-01-05'), $playlists);

    expect($random->lastCount)->toBe(5);

    $offered = [];

    for ($choice = 0; $choice < 5; $choice++) {
        $pick = rotationPicker($choice)->surprise(CalendarDate::fromString('2026-01-05'), $playlists);

        $offered[] = $pick?->genre.'|'.$pick?->mood;
    }

    expect($offered)->toBe([
        'jazz|debugging',
        'lofi|coffee-needed',
        'lofi|flow-state',
        'rock|debugging',
        'rock|flow-state',
    ]);
});

it('surprises with today pick for the pair it chose, not with any playlist', function (): void {
    $date = CalendarDate::fromString('2026-01-05');
    $playlists = rotationMixedCatalog();

    $surprise = rotationPicker(4)->surprise($date, $playlists);
    $asked = rotationPicker()->pick($date, 'rock', 'flow-state', $playlists);

    expect($surprise?->genre)->toBe('rock')
        ->and($surprise?->mood)->toBe('flow-state')
        ->and($surprise?->playlist->id)->toBe($asked?->playlist->id);
});

it('has nothing to surprise with when the catalog is empty', function (): void {
    $random = new FixedRandomSource;
    $picker = new DailyPicker($random);

    $pick = $picker->surprise(CalendarDate::fromString('2026-01-05'), []);

    expect($pick)->toBeNull()
        ->and($random->lastCount)->toBeNull();
});
