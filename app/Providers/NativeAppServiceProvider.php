<?php

declare(strict_types=1);

namespace App\Providers;

use App\Catalog\SyncLauncher;
use Illuminate\Support\Facades\Log;
use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;
use Throwable;

final class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open();

        $this->startCatalogSync();
    }

    /**
     * Starts the sync requirements 4.4 asks for just after startup.
     *
     * It goes into its own process, so this returns at once and the window draws
     * from the catalog already stored. The app has a working catalog either way,
     * so a sync that cannot even be started is worth a log line and nothing
     * more: it must never be the reason the app does not open.
     */
    private function startCatalogSync(): void
    {
        try {
            app(SyncLauncher::class)->launch();
        } catch (Throwable $exception) {
            Log::warning('Could not start the catalog sync at startup: '.$exception->getMessage());
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     *
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [];
    }
}
