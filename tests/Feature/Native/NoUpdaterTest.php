<?php

declare(strict_types=1);

/**
 * The only network call is the catalog download. AGENTS.md rule 3.
 *
 * With the updater on, NativePHP asks an update server for a new version every
 * time the packaged app starts. That would be a second network call, and
 * PRIVACY.md says there is no update ping. New versions come as a DMG on a
 * GitHub Release.
 */
it('keeps the NativePHP updater off', function (): void {
    expect(config('nativephp.updater.enabled'))->toBeFalse();
});
