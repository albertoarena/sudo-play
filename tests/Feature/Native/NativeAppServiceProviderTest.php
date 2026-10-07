<?php

declare(strict_types=1);

use App\Catalog\SyncLauncher;
use App\Providers\NativeAppServiceProvider;
use Native\Laravel\Facades\Window;
use Native\Laravel\Fakes\WindowManagerFake;
use Tests\Support\FakeSyncLauncher;

/**
 * The provider itself is UI glue, and the menu bar it will configure belongs to
 * M5. What M3 has to prove is that the startup sync is asked for, and that
 * failing to ask does not stop the app from opening.
 */
function fakeWindows(): WindowManagerFake
{
    // The fake refuses to open a window unless it has one to hand back.
    return Window::fake()->alwaysReturnWindows([new Native\Laravel\Windows\Window('main')]);
}

it('asks for a sync when the app boots', function (): void {
    $launcher = new FakeSyncLauncher;
    $this->app->instance(SyncLauncher::class, $launcher);
    fakeWindows();

    (new NativeAppServiceProvider)->boot();

    expect($launcher->launches)->toBe(1);
});

it('still opens the window when the sync cannot be started', function (): void {
    $this->app->instance(SyncLauncher::class, new class implements SyncLauncher
    {
        public function launch(): void
        {
            throw new RuntimeException('Electron is not listening');
        }
    });
    $window = fakeWindows();

    (new NativeAppServiceProvider)->boot();

    $window->assertOpenedCount(1);
});
