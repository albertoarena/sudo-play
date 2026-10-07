<?php

declare(strict_types=1);

namespace App\Providers;

use App\Catalog\CatalogStore;
use App\Catalog\DatabaseCatalogStore;
use App\Settings\DatabaseSettingsStore;
use App\Settings\SettingsStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
        $this->app->singleton(CatalogStore::class, DatabaseCatalogStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
