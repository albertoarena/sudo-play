<?php

declare(strict_types=1);

use App\Rotation\SystemRandomSource;

it('has only one answer when there is one option', function (): void {
    expect((new SystemRandomSource)->indexBelow(1))->toBe(0);
});

it('always answers inside the range', function (): void {
    $source = new SystemRandomSource;

    for ($call = 0; $call < 500; $call++) {
        $index = $source->indexBelow(7);

        expect($index)->toBeGreaterThanOrEqual(0)
            ->and($index)->toBeLessThan(7);
    }
});

it('reaches every option', function (): void {
    $source = new SystemRandomSource;

    $seen = [];

    for ($call = 0; $call < 500; $call++) {
        $seen[$source->indexBelow(4)] = true;
    }

    expect($seen)->toHaveCount(4);
});
