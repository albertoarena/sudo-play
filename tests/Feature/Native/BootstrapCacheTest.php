<?php

declare(strict_types=1);

use App\Native\BootstrapCache;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;

/*
 * NativePHP runs `artisan optimize` on every launch of the packaged app. Left
 * alone, Laravel writes the caches inside the signed bundle and macOS reports
 * "a sealed resource is missing or invalid" from then on. The three files
 * written at launch go to the user data folder instead.
 */

const BUNDLE_BASE_PATH = '/Applications/Sudo Play.app/Contents/Resources/build/app';

function userDataFolder(): string
{
    return sys_get_temp_dir().'/sudo-play-'.bin2hex(random_bytes(4));
}

function forgetCacheVariable(string $name): void
{
    putenv($name);
    unset($_ENV[$name], $_SERVER[$name]);
}

beforeEach(function (): void {
    foreach ([...array_keys(BootstrapCache::FILES), 'APP_PACKAGES_CACHE', 'APP_SERVICES_CACHE'] as $name) {
        forgetCacheVariable($name);
    }
});

afterEach(function (): void {
    foreach ([...array_keys(BootstrapCache::FILES), 'APP_PACKAGES_CACHE', 'APP_SERVICES_CACHE'] as $name) {
        forgetCacheVariable($name);
    }
});

it('points the launch caches at the user data folder inside a packaged app', function (): void {
    $userData = userDataFolder();

    BootstrapCache::keepOutsideTheBundle(BUNDLE_BASE_PATH, $userData);

    expect(getenv('APP_CONFIG_CACHE'))->toBe($userData.'/bootstrap/cache/config.php')
        ->and($_ENV['APP_ROUTES_CACHE'])->toBe($userData.'/bootstrap/cache/routes-v7.php')
        ->and($_SERVER['APP_EVENTS_CACHE'])->toBe($userData.'/bootstrap/cache/events.php')
        ->and(is_dir($userData.'/bootstrap/cache'))->toBeTrue();
});

it('leaves packages and services in the bundle, where the build put them', function (): void {
    $userData = userDataFolder();
    $previous = Container::getInstance();

    BootstrapCache::keepOutsideTheBundle(BUNDLE_BASE_PATH, $userData.'/');

    try {
        $app = new Application(BUNDLE_BASE_PATH);

        expect($app->getCachedConfigPath())->toBe($userData.'/bootstrap/cache/config.php')
            ->and($app->getCachedRoutesPath())->toBe($userData.'/bootstrap/cache/routes-v7.php')
            ->and($app->getCachedEventsPath())->toBe($userData.'/bootstrap/cache/events.php')
            ->and($app->getCachedPackagesPath())->toBe(BUNDLE_BASE_PATH.'/bootstrap/cache/packages.php')
            ->and($app->getCachedServicesPath())->toBe(BUNDLE_BASE_PATH.'/bootstrap/cache/services.php');
    } finally {
        Container::setInstance($previous);
    }
});

it('keeps a path that is already set, as a secure build does', function (): void {
    putenv('APP_CONFIG_CACHE=/elsewhere/config.php');

    BootstrapCache::keepOutsideTheBundle(BUNDLE_BASE_PATH, userDataFolder());

    expect(getenv('APP_CONFIG_CACHE'))->toBe('/elsewhere/config.php')
        ->and(getenv('APP_ROUTES_CACHE'))->not->toBeFalse();
});

it('changes nothing when running from source', function (): void {
    BootstrapCache::keepOutsideTheBundle('/Users/someone/dev/sudo-play', userDataFolder());

    foreach (array_keys(BootstrapCache::FILES) as $name) {
        expect(getenv($name))->toBeFalse();
    }
});

it('changes nothing without a user data folder', function (string|false $userData): void {
    BootstrapCache::keepOutsideTheBundle(BUNDLE_BASE_PATH, $userData);

    foreach (array_keys(BootstrapCache::FILES) as $name) {
        expect(getenv($name))->toBeFalse()
            ->and(isset($_ENV[$name]))->toBeFalse();
    }
})->with(['missing' => false, 'empty' => '']);
