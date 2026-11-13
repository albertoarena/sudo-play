<?php

declare(strict_types=1);

namespace App\Native;

/**
 * Keeps the caches Laravel writes at launch out of the signed app.
 *
 * NativePHP runs `artisan optimize` every time the packaged app starts. It
 * moves the cache files to a writable folder only for a secure build, which
 * ours is not, so Laravel writes them into `bootstrap/cache` inside the bundle.
 * The added files break the code signature: after the first launch macOS says
 * "a sealed resource is missing or invalid". From a mounted DMG the write
 * fails, because the volume is read only.
 *
 * Electron passes the user data folder to every PHP process, the `optimize`
 * run included, so the three files written at launch go there. Packages and
 * services stay in the bundle: the build writes them, they are part of the
 * signature, and in the user data folder they would go stale after an update.
 * Running from source changes nothing, since the base path is not in an app.
 */
final class BootstrapCache
{
    /** @var array<string, string> */
    public const array FILES = [
        'APP_CONFIG_CACHE' => 'config.php',
        'APP_ROUTES_CACHE' => 'routes-v7.php',
        'APP_EVENTS_CACHE' => 'events.php',
    ];

    public static function keepOutsideTheBundle(string $basePath, string|false $userDataPath): void
    {
        if (! str_contains($basePath, '.app/Contents/Resources/') || $userDataPath === false || $userDataPath === '') {
            return;
        }

        $folder = rtrim($userDataPath, '/').'/bootstrap/cache';

        if (! is_dir($folder)) {
            @mkdir($folder, 0755, true);
        }

        foreach (self::FILES as $name => $file) {
            // A secure build sets them already.
            if (getenv($name) !== false || isset($_ENV[$name]) || isset($_SERVER[$name])) {
                continue;
            }

            $path = $folder.'/'.$file;
            putenv($name.'='.$path);
            $_ENV[$name] = $path;
            $_SERVER[$name] = $path;
        }
    }
}
