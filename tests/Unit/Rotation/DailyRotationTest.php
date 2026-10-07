<?php

declare(strict_types=1);

use App\Rotation\CalendarDate;
use App\Rotation\DailyRotation;
use Tests\Support\CalendarDays;

/**
 * Worked out in the test, so the coprime check does not lean on the same code
 * it is checking.
 */
function rotationGcd(int $a, int $b): int
{
    while ($b !== 0) {
        [$a, $b] = [$b, $a % $b];
    }

    return $a;
}

/**
 * Genre and mood pairs to try every property against, so no result rests on
 * one lucky hash.
 *
 * @return list<array{string, string}>
 */
function rotationPairs(): array
{
    return [
        ['rock', 'flow-state'],
        ['jazz', 'debugging'],
        ['lofi', 'coffee-needed'],
        ['electronic', 'friday-deploy'],
        ['classical', 'blue-monday'],
        ['pop', 'shipped-it'],
    ];
}

it('puts the only candidate at index 0, whatever the day', function (): void {
    foreach (CalendarDays::run('2026-01-01', 40) as $date) {
        expect(DailyRotation::indexFor($date, 'rock', 'flow-state', 1))->toBe(0);
    }
});

it('gives the same index for the same day, genre, mood and count', function (): void {
    $date = CalendarDate::fromString('2026-01-19');

    expect(DailyRotation::indexFor($date, 'rock', 'flow-state', 5))
        ->toBe(DailyRotation::indexFor($date, 'rock', 'flow-state', 5));
});

it('always lands inside the candidate list', function (int $count): void {
    foreach (rotationPairs() as [$genre, $mood]) {
        foreach (CalendarDays::run('2026-01-01', 120) as $date) {
            $index = DailyRotation::indexFor($date, $genre, $mood, $count);

            expect($index)->toBeGreaterThanOrEqual(0)
                ->and($index)->toBeLessThan($count);
        }
    }
})->with([1, 2, 3, 4, 5, 7, 12, 40]);

it('never lands on the same index two days running', function (int $count): void {
    foreach (rotationPairs() as [$genre, $mood]) {
        $previous = null;

        foreach (CalendarDays::run('2026-01-01', 400) as $date) {
            $index = DailyRotation::indexFor($date, $genre, $mood, $count);

            if ($previous !== null) {
                expect($index)->not->toBe(
                    $previous,
                    sprintf('%s %s repeated index %d on %s', $genre, $mood, $index, $date->value),
                );
            }

            $previous = $index;
        }
    }
})->with([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 40]);

it('uses a step that is coprime with the candidate count', function (int $count): void {
    foreach (rotationPairs() as [$genre, $mood]) {
        $step = DailyRotation::stepFor($genre, $mood, $count);

        expect($step)->toBeGreaterThanOrEqual(1)
            ->and($step)->toBeLessThan($count)
            ->and(rotationGcd($step, $count))->toBe(
                1,
                sprintf('step %d shares a factor with count %d', $step, $count),
            );
    }
})->with([2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 24, 36, 40, 60]);

it('steps by one when there are two candidates, so they alternate', function (): void {
    foreach (rotationPairs() as [$genre, $mood]) {
        expect(DailyRotation::stepFor($genre, $mood, 2))->toBe(1);
    }
});

it('gives every candidate a turn within one round', function (int $count): void {
    foreach (rotationPairs() as [$genre, $mood]) {
        $seen = [];

        foreach (CalendarDays::run('2026-01-01', $count) as $date) {
            $seen[] = DailyRotation::indexFor($date, $genre, $mood, $count);
        }

        expect(array_unique($seen))->toHaveCount($count);
    }
})->with([2, 3, 4, 5, 7, 12]);

it('runs a different cycle for a different genre or mood', function (): void {
    $run = CalendarDays::run('2026-01-01', 30);

    $sequence = function (string $genre, string $mood) use ($run): array {
        return array_map(
            fn (CalendarDate $date): int => DailyRotation::indexFor($date, $genre, $mood, 5),
            $run,
        );
    };

    expect($sequence('rock', 'flow-state'))->not->toBe($sequence('jazz', 'flow-state'))
        ->and($sequence('rock', 'flow-state'))->not->toBe($sequence('rock', 'debugging'));
});

it('still lands inside the list for a day before 1970', function (): void {
    $index = DailyRotation::indexFor(CalendarDate::fromString('1969-07-20'), 'rock', 'flow-state', 5);

    expect($index)->toBeGreaterThanOrEqual(0)
        ->and($index)->toBeLessThan(5);
});
