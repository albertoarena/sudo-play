// @vitest-environment node
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';

// NativePHP ships its Electron main process as compiled JS in dist/, and the
// build uses it as is. Our Composer patch wires the watchdog into it. These
// checks fail if an update to nativephp/desktop drops or half-applies it.
const dist = join(__dirname, '../../vendor/nativephp/desktop/resources/electron/electron-plugin/dist');

function source(file: string): string {
    return readFileSync(join(dist, file), 'utf8');
}

describe('nativephp desktop patch', () => {
    it('lets php.js restart the server on the same port', () => {
        const php = source('server/php.js');

        expect(php).toContain('restart: () => launch(phpPort, RESTART_TIMEOUT_MS)');
    });

    it('supervises the server in server/index.js', () => {
        const server = source('server/index.js');

        expect(server).toContain("from './phpServerWatchdog.js'");
        expect(server).toContain('superviseServer(result, result.process, onReplaced)');
        expect(server).toContain('app.relaunch()');
    });

    it('stops restarts when the app quits and swaps the process it kills', () => {
        const main = source('index.js');

        expect(main).toMatch(/app\.on\('before-quit', \(event\) => __awaiter\(this, void 0, void 0, function\* \(\) \{\s+markQuitting\(\);/);
        expect(main).toContain('this.replaceProcess(oldProcess, newProcess)');
    });

    it('copies the console to electron.log in a packaged app', () => {
        const main = source('index.js');

        expect(main).toContain("from './server/electronLog.js'");
        expect(main).toMatch(/if \(app\.isPackaged\) \{\s+copyConsoleTo\(createLogFile\(join\(app\.getPath\('userData'\), 'storage', 'logs', 'electron\.log'\)\)\);/);
    });
});
