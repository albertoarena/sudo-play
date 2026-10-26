<?php

declare(strict_types=1);

namespace App\Providers;

use App\Catalog\BundledCatalogSource;
use App\Catalog\CatalogDownloader;
use App\Catalog\CatalogSource;
use App\Catalog\CatalogStore;
use App\Catalog\DatabaseCatalogStore;
use App\Catalog\HttpCatalogDownloader;
use App\Catalog\SyncLauncher;
use App\MenuBar\MenuBarWindow;
use App\MenuBar\Quitter;
use App\MenuBar\SettingsWindow;
use App\MenuBar\Today;
use App\Native\ChildProcessSyncLauncher;
use App\Native\LocalToday;
use App\Native\NativeLoginItem;
use App\Native\NativeMenuBarWindow;
use App\Native\NativeNotifier;
use App\Native\NativeQuitter;
use App\Native\NativeSettingsWindow;
use App\Native\OpenMenuBarOnReminderClick;
use App\Native\ShellUrlOpener;
use App\Notifications\Notifier;
use App\Player\Clock;
use App\Player\PlayerInterface;
use App\Player\PlayerResolver;
use App\Player\SystemClock;
use App\Player\UrlOpener;
use App\Rotation\RandomSource;
use App\Rotation\SystemRandomSource;
use App\Settings\DatabaseSettingsStore;
use App\Settings\SettingsStore;
use App\SettingsWindow\LoginItem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Native\Desktop\Events\Notifications\NotificationClicked;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
        $this->app->singleton(CatalogStore::class, DatabaseCatalogStore::class);
        $this->app->singleton(CatalogSource::class, static fn (): CatalogSource => BundledCatalogSource::bundled());
        $this->app->singleton(CatalogDownloader::class, HttpCatalogDownloader::class);
        $this->app->singleton(SyncLauncher::class, ChildProcessSyncLauncher::class);
        $this->app->singleton(UrlOpener::class, ShellUrlOpener::class);
        $this->app->singleton(PlayerInterface::class, PlayerResolver::class);
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(RandomSource::class, SystemRandomSource::class);
        $this->app->singleton(Quitter::class, NativeQuitter::class);
        $this->app->singleton(Today::class, static fn (): Today => LocalToday::system());
        $this->app->singleton(Notifier::class, NativeNotifier::class);
        $this->app->singleton(LoginItem::class, NativeLoginItem::class);
        $this->app->singleton(SettingsWindow::class, NativeSettingsWindow::class);
        $this->app->singleton(MenuBarWindow::class, NativeMenuBarWindow::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(NotificationClicked::class, OpenMenuBarOnReminderClick::class);
    }
}
