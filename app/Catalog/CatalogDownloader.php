<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Fetches a catalog file over the network.
 *
 * This is the app's only network call. Requirements 3 and 4.4.
 *
 * It is an interface so the sync can be tested without faking HTTP, and so the
 * limits that matter, a short timeout and a size cap, live in one place rather
 * than being repeated by every caller.
 */
interface CatalogDownloader
{
    public function download(string $url): DownloadResult;
}
