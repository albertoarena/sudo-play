<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

/**
 * The app has no accounts. AGENTS.md rule 3.
 *
 * The Laravel skeleton ships tables for users and password resets. They stay
 * out, so nothing can start to depend on them. The sessions table stays, as
 * the session driver is the database.
 */
it('has no account tables', function (string $table): void {
    expect(Schema::hasTable($table))->toBeFalse();
})->with(['users', 'password_reset_tokens']);

it('keeps the sessions table', function (): void {
    expect(Schema::hasTable('sessions'))->toBeTrue();
});

it('runs the sessions migration on a database that already has the table', function (): void {
    // A database from before the rename got the table from the old users migration
    $migration = require database_path('migrations/0001_01_01_000000_create_sessions_table.php');

    $migration->up();

    expect(Schema::hasTable('sessions'))->toBeTrue();
});

it('has no user model', function (): void {
    expect(class_exists('App\Models\User'))->toBeFalse();
});
