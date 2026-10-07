<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/** @return list<Event> */
function catalogSyncEvents(): array
{
    // schedule:list makes the console kernel load routes/console.php, which is
    // where the schedule is declared.
    test()->artisan('schedule:list')->run();

    return array_values(array_filter(
        app(Schedule::class)->events(),
        static fn (Event $event): bool => str_contains((string) $event->command, 'catalog:sync'),
    ));
}

it('syncs the catalog once a day', function (): void {
    $events = catalogSyncEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('0 0 * * *');
});

it('does not start a second sync while one is still running', function (): void {
    expect(catalogSyncEvents()[0]->withoutOverlapping)->toBeTrue();
});
