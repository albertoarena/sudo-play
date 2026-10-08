<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Catalog\Catalog;
use App\Catalog\CatalogStore;
use App\Catalog\StoredCatalog;
use App\MenuBar\Today;
use App\Rotation\CalendarDate;
use App\Rotation\DailyPicker;
use App\Settings\SettingsStore;
use DateTimeImmutable;

/**
 * Puts the menu bar on in-memory stores and a fixed day, for the feature tests.
 */
final class MenuBarSetup
{
    public const string TODAY = '2026-10-08';

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function with(?Catalog $catalog, array $settings = [], ?DateTimeImmutable $syncedAt = null): InMemorySettingsStore
    {
        $store = new InMemorySettingsStore($settings);

        app()->instance(SettingsStore::class, $store);
        app()->instance(CatalogStore::class, new InMemoryCatalogStore(
            $catalog === null ? null : new StoredCatalog($catalog, $syncedAt),
        ));
        app()->instance(Today::class, new FixedToday(self::TODAY));

        return $store;
    }

    /** The title the rotation gives for a pair today, after a number of Next presses. */
    public static function expectedTitle(string $genre, string $mood, int $presses = 0): string
    {
        $pick = (new DailyPicker(new FixedRandomSource))->next(
            CalendarDate::fromString(self::TODAY),
            $genre,
            $mood,
            MenuBarCatalog::full()->playlists,
            $presses,
        );

        return (string) $pick?->playlist->title;
    }
}
