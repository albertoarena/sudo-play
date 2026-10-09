<?php

declare(strict_types=1);

use App\Http\Controllers\SettingsController;
use App\Player\UrlOpener;
use App\Settings\SettingKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Support\CatalogFixtures;
use Tests\Support\MenuBarCatalog;
use Tests\Support\MenuBarSetup;
use Tests\Support\RecordingUrlOpener;

/*
 * The buttons in the settings window that do something other than save:
 * "Update catalog now" and the links in About and Credits. Requirements 7.2.
 */

afterEach(function (): void {
    Carbon::setTestNow();
});

describe('update catalog now', function (): void {
    it('downloads the catalog from the saved URL and answers with the new date', function (): void {
        Carbon::setTestNow('2026-10-08 10:15:00');
        MenuBarSetup::with(MenuBarCatalog::full(), [SettingKey::CatalogUrl->value => 'https://example.com/fork.yaml']);
        Http::fake(['https://example.com/fork.yaml' => Http::response(CatalogFixtures::contents('valid.yaml'))]);

        $this->postJson('/settings/update-catalog')
            ->assertOk()
            ->assertJsonPath('updated', true)
            ->assertJsonPath('message', 'Catalog updated.')
            ->assertJsonPath('catalogDate', '2026-10-08');
    });

    it('keeps the catalog it has when the download fails, and says so', function (): void {
        MenuBarSetup::with(MenuBarCatalog::full(), syncedAt: new DateTimeImmutable('2026-10-01 08:00:00'));
        Http::fake([SettingKey::DEFAULT_CATALOG_URL => Http::response('Not found', 404)]);

        $this->postJson('/settings/update-catalog')
            ->assertOk()
            ->assertJsonPath('updated', false)
            ->assertJsonPath('message', 'Could not update the catalog. Keeping the one you have.')
            ->assertJsonPath('catalogDate', '2026-10-01');
    });
});

it('opens the GitHub page in the browser, not in the window', function (): void {
    $opener = RecordingUrlOpener::working();
    app()->instance(UrlOpener::class, $opener);

    $this->postJson('/settings/open-repository')->assertNoContent();

    expect($opener->opened)->toBe([SettingsController::REPOSITORY_URL]);
});

it('opens PRIVACY.md on GitHub in the browser', function (): void {
    $opener = RecordingUrlOpener::working();
    app()->instance(UrlOpener::class, $opener);

    $this->postJson('/settings/open-privacy')->assertNoContent();

    expect($opener->opened)->toBe(['https://github.com/albertoarena/sudo-play/blob/main/PRIVACY.md']);
});

it('opens the contributors page on GitHub in the browser', function (): void {
    $opener = RecordingUrlOpener::working();
    app()->instance(UrlOpener::class, $opener);

    $this->postJson('/settings/open-contributors')->assertNoContent();

    expect($opener->opened)->toBe(['https://github.com/albertoarena/sudo-play/graphs/contributors']);
});
