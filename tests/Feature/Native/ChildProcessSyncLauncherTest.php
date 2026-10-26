<?php

declare(strict_types=1);

use App\Catalog\SyncLauncher;
use App\Native\ChildProcessSyncLauncher;
use Native\Desktop\Facades\ChildProcess;

/*
 * The NativePHP fake spreads everything it recorded into the closure by name,
 * so the closures here take the whole signature even where they only look at
 * part of it.
 */

it('starts the sync in a process of its own, so the window does not wait for it', function (): void {
    $child = ChildProcess::fake();

    (new ChildProcessSyncLauncher)->launch();

    $child->assertArtisan(
        fn (array|string $cmd, string $alias, ?array $env, ?bool $persistent, ?array $iniSettings): bool => $cmd === 'catalog:sync'
            && $alias === ChildProcessSyncLauncher::ALIAS,
    );
});

it('does not keep the process alive after the sync is done', function (): void {
    $child = ChildProcess::fake();

    (new ChildProcessSyncLauncher)->launch();

    $child->assertArtisan(
        fn (array|string $cmd, string $alias, ?array $env, ?bool $persistent, ?array $iniSettings): bool => $persistent === false,
    );
});

it('starts exactly one process', function (): void {
    $child = ChildProcess::fake();

    (new ChildProcessSyncLauncher)->launch();

    expect($child->artisans)->toHaveCount(1)
        ->and($child->starts)->toBe([])
        ->and($child->phps)->toBe([]);
});

it('resolves the child process launcher for the rest of the app', function (): void {
    expect(app(SyncLauncher::class))->toBeInstanceOf(ChildProcessSyncLauncher::class);
});
