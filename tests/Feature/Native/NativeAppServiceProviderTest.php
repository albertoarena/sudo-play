<?php

declare(strict_types=1);

use App\Catalog\SyncLauncher;
use App\Providers\NativeAppServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeSyncLauncher;

/*
 * The provider is UI glue. NativePHP ships no fake for the menu bar, but its
 * client talks to Electron through the Laravel HTTP client, so faking HTTP
 * shows what would have reached Electron.
 */

function menuBarCreated(): ?Request
{
    $sent = Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), 'menu-bar/create'));

    return $sent->first()[0] ?? null;
}

// Requirements 7.1 says about 360 by 480. The window is 520 tall so a fallback
// note and the autoplay hint fit together without scrolling.
it('creates the menu bar with the window size from requirements 7.1', function (): void {
    Http::fake();
    $this->app->instance(SyncLauncher::class, new FakeSyncLauncher);

    (new NativeAppServiceProvider)->boot();

    $request = menuBarCreated();

    expect($request)->not->toBeNull()
        ->and($request['width'])->toBe(360)
        ->and($request['height'])->toBe(520)
        ->and($request['url'])->toBe(url('/'))
        ->and($request['showDockIcon'])->toBeFalse()
        ->and($request['resizable'])->toBeFalse();
});

// NativePHP turns this off unless asked. Off, the window belongs to the Space
// it was first shown on, and clicking the icon from another Space makes macOS
// switch back to that one instead of opening the window where the user is.
it('shows the menu bar window on every Space', function (): void {
    Http::fake();
    $this->app->instance(SyncLauncher::class, new FakeSyncLauncher);

    (new NativeAppServiceProvider)->boot();

    expect(menuBarCreated()['showOnAllWorkspaces'] ?? null)->toBeTrue();
});

it('uses a template image, so the icon follows light and dark mode', function (): void {
    Http::fake();
    $this->app->instance(SyncLauncher::class, new FakeSyncLauncher);

    (new NativeAppServiceProvider)->boot();

    $icon = menuBarCreated()['icon'] ?? '';

    // macOS treats a file whose name ends in "Template" as a template image.
    expect($icon)->toEndWith('Template.png')
        ->and(is_file($icon))->toBeTrue()
        ->and(is_file(str_replace('.png', '@2x.png', $icon)))->toBeTrue();
});

it('opens no other window', function (): void {
    Http::fake();
    $this->app->instance(SyncLauncher::class, new FakeSyncLauncher);

    (new NativeAppServiceProvider)->boot();

    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'window/open'));
});

it('asks for a sync when the app boots', function (): void {
    Http::fake();
    $launcher = new FakeSyncLauncher;
    $this->app->instance(SyncLauncher::class, $launcher);

    (new NativeAppServiceProvider)->boot();

    expect($launcher->launches)->toBe(1);
});

it('still creates the menu bar when the sync cannot be started', function (): void {
    Http::fake();
    $this->app->instance(SyncLauncher::class, new class implements SyncLauncher
    {
        public function launch(): void
        {
            throw new RuntimeException('Electron is not listening');
        }
    });

    (new NativeAppServiceProvider)->boot();

    expect(menuBarCreated())->not->toBeNull();
});
