<?php

declare(strict_types=1);

use App\Catalog\BundledCatalogSource;
use App\Catalog\CatalogSource;
use App\Catalog\CatalogStore;
use App\Catalog\CatalogSync;
use App\Settings\SettingKey;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\CatalogFixtures;

beforeEach(function (): void {
    $this->app->instance(CatalogSource::class, new BundledCatalogSource(
        dirname(__DIR__, 2).'/fixtures/catalogs/no-playlists.yaml',
    ));
});

it('is resolvable, so the scheduler and the startup run can reach it', function (): void {
    expect(app(CatalogSync::class))->toBeInstanceOf(CatalogSync::class);
});

it('loads the bundled catalog and then downloads, on a first run', function (): void {
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(CatalogFixtures::contents('valid.yaml'))]);

    $this->artisan('catalog:sync')
        ->expectsOutputToContain('Loaded the bundled catalog')
        ->expectsOutputToContain('Updated the catalog')
        ->assertSuccessful();

    expect(app(CatalogStore::class)->current()?->catalog->playlists)->toHaveCount(2);
});

it('says the bundled catalog was not needed once one is stored', function (): void {
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(CatalogFixtures::contents('valid.yaml'))]);
    $this->artisan('catalog:sync')->assertSuccessful();

    $this->artisan('catalog:sync')
        ->expectsOutputToContain('already stored')
        ->assertSuccessful();
});

it('succeeds when a download fails but a catalog is in place, because the app works offline', function (): void {
    Http::fake(fn (): never => throw new ConnectionException('Could not resolve host'));

    $this->artisan('catalog:sync')
        ->expectsOutputToContain('Loaded the bundled catalog')
        ->expectsOutputToContain('could not be downloaded')
        ->assertSuccessful();

    expect(app(CatalogStore::class)->current())->not->toBeNull();
});

it('fails when it ends with no catalog at all', function (): void {
    $this->app->instance(CatalogSource::class, new BundledCatalogSource('/does/not/exist.yaml'));
    Http::fake(fn (): never => throw new ConnectionException('Could not resolve host'));

    $this->artisan('catalog:sync')->assertFailed();

    expect(app(CatalogStore::class)->current())->toBeNull();
});

it('shows the date of the catalog it ended up with', function (): void {
    $this->travelTo('2026-10-07 08:30:00');
    Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response(CatalogFixtures::contents('valid.yaml'))]);

    $this->artisan('catalog:sync')
        ->expectsOutputToContain('2026-10-07 08:30')
        ->assertSuccessful();
});
