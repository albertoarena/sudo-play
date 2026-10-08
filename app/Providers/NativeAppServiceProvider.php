<?php

declare(strict_types=1);

namespace App\Providers;

use App\Catalog\SyncLauncher;
use Illuminate\Support\Facades\Log;
use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\MenuBar;
use Throwable;

final class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Requirements 7.1: a small window, about 360 by 480. It is 520 tall so a
     * fallback note and the autoplay hint fit together without scrolling.
     */
    public const int WIDTH = 360;

    public const int HEIGHT = 520;

    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $this->createMenuBar();

        $this->startCatalogSync();
    }

    /**
     * The app lives in the menu bar only: no Dock icon and no other window.
     *
     * The icon is a template image. macOS reads the name ending in "Template"
     * and recolours the black shape for light and dark mode, and picks the
     * `@2x` file on a Retina screen by itself.
     */
    private function createMenuBar(): void
    {
        MenuBar::create()
            ->route('menubar')
            ->icon(public_path('menuBarIconTemplate.png'))
            ->tooltip('Sudo Play')
            ->width(self::WIDTH)
            ->height(self::HEIGHT)
            ->resizable(false)
            ->showDockIcon(false);
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
