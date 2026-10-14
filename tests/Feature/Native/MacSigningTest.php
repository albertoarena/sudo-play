<?php

declare(strict_types=1);

/*
 * The signed macOS build is made by scripts/macos, not by NativePHP. NativePHP
 * has no setting for entitlements or Info.plist keys, and its defaults break
 * Spotify control under the hardened runtime that notarization requires. These
 * tests keep our files in step with what NativePHP ships, so an update to it
 * cannot quietly drop an entitlement or add a usage string we do not want.
 */

function nativePhpBuilderConfig(): string
{
    return (string) file_get_contents(base_path('vendor/nativephp/electron/resources/js/electron-builder.js'));
}

/**
 * @return array<string, bool>
 */
function entitlementsIn(string $path): array
{
    $plist = simplexml_load_file($path);

    expect($plist)->not->toBeFalse();

    $entitlements = [];
    $nodes = $plist->dict->children();

    for ($i = 0; $i < count($nodes); $i += 2) {
        $entitlements[(string) $nodes[$i]] = $nodes[$i + 1]->getName() === 'true';
    }

    return $entitlements;
}

/**
 * @return array{bundle_id: string, set: array<string, string>, remove: list<string>}
 */
function macInfo(): array
{
    /** @var array{bundle_id: string, set: array<string, string>, remove: list<string>} */
    return json_decode((string) file_get_contents(base_path('scripts/macos/info.json')), true, flags: JSON_THROW_ON_ERROR);
}

it('defaults the bundle id to Sudo Play, not to NativePHP', function (): void {
    $saved = [$_ENV['NATIVEPHP_APP_ID'] ?? null, $_SERVER['NATIVEPHP_APP_ID'] ?? null, getenv('NATIVEPHP_APP_ID')];

    unset($_ENV['NATIVEPHP_APP_ID'], $_SERVER['NATIVEPHP_APP_ID']);
    putenv('NATIVEPHP_APP_ID');

    try {
        $config = require config_path('nativephp.php');
    } finally {
        [$env, $server, $getenv] = $saved;
        if ($env !== null) {
            $_ENV['NATIVEPHP_APP_ID'] = $env;
        }
        if ($server !== null) {
            $_SERVER['NATIVEPHP_APP_ID'] = $server;
        }
        if ($getenv !== false) {
            putenv("NATIVEPHP_APP_ID={$getenv}");
        }
    }

    expect($config['app_id'])->toBe('com.albertoarena.sudoplay');
});

it('signs for the bundle id in the config', function (): void {
    expect(macInfo()['bundle_id'])->toBe('com.albertoarena.sudoplay');
});

it('keeps every entitlement NativePHP signs with', function (): void {
    $ours = entitlementsIn(base_path('scripts/macos/entitlements.plist'));
    $theirs = entitlementsIn(base_path('vendor/nativephp/electron/resources/js/build/entitlements.mac.plist'));

    expect($theirs)->not->toBeEmpty();

    foreach ($theirs as $key => $value) {
        expect($ours)->toHaveKey($key)
            ->and($ours[$key])->toBe($value);
    }
});

it('may send Apple events, so Play can control Spotify', function (): void {
    expect(entitlementsIn(base_path('scripts/macos/entitlements.plist')))
        ->toHaveKey('com.apple.security.automation.apple-events', true);
});

it('explains to the user why it controls Spotify', function (): void {
    $reason = macInfo()['set']['NSAppleEventsUsageDescription'] ?? '';

    expect($reason)->toContain('Spotify')
        ->not->toContain('—');
});

it('removes every usage string NativePHP adds', function (): void {
    preg_match_all('/\b(NS\w+UsageDescription)\b/', nativePhpBuilderConfig(), $matches);

    expect($matches[1])->not->toBeEmpty()
        ->and(macInfo()['remove'])->toEqualCanonicalizing(array_values(array_unique($matches[1])));
});
