<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

function catalogFixturePath(string $name): string
{
    return 'tests/fixtures/catalogs/'.$name;
}

it('passes a valid catalog', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('valid.yaml')])
        ->expectsOutputToContain('2 playlists')
        ->assertExitCode(0);
});

it('fails when the file is not there, and says which file', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('not-here.yaml')])
        ->expectsOutputToContain('not-here.yaml')
        ->assertExitCode(1);
});

it('reads catalog/playlists.yaml when no path is given', function (): void {
    $default = base_path('catalog/playlists.yaml');
    $before = file_exists($default) ? file_get_contents($default) : null;

    file_put_contents($default, (string) file_get_contents(base_path(catalogFixturePath('valid.yaml'))));

    try {
        artisan('catalog:validate')
            ->expectsOutputToContain('2 playlists')
            ->assertExitCode(0)
            ->run();
    } finally {
        if ($before === null) {
            unlink($default);
        } else {
            file_put_contents($default, $before);
        }
    }
});

it('fails on broken yaml and says the file was not read', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('broken-yaml.yaml')])
        ->expectsOutputToContain('YAML')
        ->assertExitCode(1);
});

it('fails on a version it does not know', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('wrong-version.yaml')])
        ->expectsOutputToContain('version')
        ->assertExitCode(1);
});

it('fails on invalid rows and says why each one was dropped', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('invalid-playlists.yaml')])
        ->expectsOutputToContain('unknown genre "pop"')
        ->expectsOutputToContain('unknown mood "shipped-it"')
        ->expectsOutputToContain('rock-bad-url-01')
        ->assertExitCode(1);
});

it('passes a playlist with unknown fields and reports them as warnings', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('unknown-field.yaml')])
        ->expectsOutputToContain('unknown field "tier"')
        ->assertExitCode(0);
});

it('passes an empty playlist list during development', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('no-playlists.yaml')])
        ->assertExitCode(0);
});

it('fails an empty playlist list with --release', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('no-playlists.yaml'), '--release' => true])
        ->expectsOutputToContain('no playlists')
        ->assertExitCode(1);
});

it('fails a leftover TODO with --release', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('todo-fields.yaml'), '--release' => true])
        ->expectsOutputToContain('TODO')
        ->assertExitCode(1);
});

it('passes a leftover TODO without --release', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('todo-fields.yaml')])
        ->assertExitCode(0);
});

it('passes a full catalog with --release', function (): void {
    artisan('catalog:validate', ['path' => catalogFixturePath('valid.yaml'), '--release' => true])
        ->assertExitCode(0);
});
