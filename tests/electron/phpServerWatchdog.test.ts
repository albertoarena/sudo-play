// @vitest-environment node
import { describe, expect, it } from 'vitest';
// The watchdog ships inside NativePHP's Electron plugin through our Composer
// patch (patches/nativephp-electron-php-server-restart.patch). If the patch
// stops applying, this import fails and so does CI.
import {
    createPhpServerWatchdog,
    MAX_RECOVERIES,
    RECOVERY_WINDOW_MS,
} from '../../vendor/nativephp/electron/resources/js/electron-plugin/dist/server/phpServerWatchdog.js';

function clock(start = 1_000_000) {
    let time = start;

    return {
        now: () => time,
        advance: (ms: number) => {
            time += ms;
        },
    };
}

describe('php server watchdog', () => {
    it('restarts a server that exits while the app is running', () => {
        const watchdog = createPhpServerWatchdog({ now: clock().now });

        expect(watchdog.onExit()).toBe('restart');
    });

    it('leaves the server alone when the app is quitting', () => {
        const watchdog = createPhpServerWatchdog({ now: clock().now });

        watchdog.markQuitting();

        expect(watchdog.isQuitting()).toBe(true);
        expect(watchdog.onExit()).toBe('ignore');
        expect(watchdog.onRestartFailed()).toBe('ignore');
    });

    it('relaunches the app when the restart on the same port fails', () => {
        const watchdog = createPhpServerWatchdog({ now: clock().now });

        watchdog.onExit();

        expect(watchdog.onRestartFailed()).toBe('relaunch');
    });

    it('gives up after three recoveries in five minutes', () => {
        const time = clock();
        const watchdog = createPhpServerWatchdog({ now: time.now });

        expect(MAX_RECOVERIES).toBe(3);
        expect(RECOVERY_WINDOW_MS).toBe(5 * 60 * 1000);

        for (let i = 0; i < 3; i++) {
            expect(watchdog.onExit()).toBe('restart');
            time.advance(60 * 1000);
        }

        expect(watchdog.onExit()).toBe('give-up');
        expect(watchdog.onRestartFailed()).toBe('give-up');
    });

    it('counts a relaunch as a recovery', () => {
        const watchdog = createPhpServerWatchdog({ now: clock().now });

        watchdog.onExit();
        watchdog.onRestartFailed();
        watchdog.onExit();

        expect(watchdog.onExit()).toBe('give-up');
    });

    it('forgets recoveries older than five minutes', () => {
        const time = clock();
        const watchdog = createPhpServerWatchdog({ now: time.now });

        for (let i = 0; i < 3; i++) {
            watchdog.onExit();
        }
        time.advance(RECOVERY_WINDOW_MS);

        expect(watchdog.onExit()).toBe('restart');
    });

    it('carries recoveries across a relaunch through the history', () => {
        const time = clock();
        const before = createPhpServerWatchdog({ now: time.now });

        before.onExit();
        before.onRestartFailed();
        time.advance(1000);

        const after = createPhpServerWatchdog({ now: time.now, history: before.history() });

        expect(after.onExit()).toBe('restart');
        expect(after.onExit()).toBe('give-up');
    });

    it('drops stale or invalid entries from the history it is given', () => {
        const time = clock(10 * RECOVERY_WINDOW_MS);
        const watchdog = createPhpServerWatchdog({
            now: time.now,
            history: [1, 2, 'x', null] as unknown as number[],
        });

        expect(watchdog.history()).toEqual([]);
    });
});
