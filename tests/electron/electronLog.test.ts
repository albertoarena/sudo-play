// @vitest-environment node
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
// Ships inside NativePHP's Electron plugin through our Composer patch, like
// the watchdog. See phpServerWatchdog.test.ts.
import {
    copyConsoleTo,
    createLogFile,
    MAX_LOG_BYTES,
} from '../../vendor/nativephp/electron/resources/js/electron-plugin/dist/server/electronLog.js';

const at = () => new Date('2026-10-22T09:30:00.000Z');

let dir: string;
let file: string;

beforeEach(() => {
    dir = mkdtempSync(join(tmpdir(), 'electron-log-'));
    file = join(dir, 'logs', 'electron.log');
});

afterEach(() => {
    rmSync(dir, { recursive: true, force: true });
});

describe('electron log file', () => {
    it('rotates at about 1 MB', () => {
        expect(MAX_LOG_BYTES).toBe(1024 * 1024);
    });

    it('writes a timestamped line with the level, creating the folder', () => {
        const write = createLogFile(file, { now: at });

        write('ERROR', ['PHP server exited with code %d', 143]);

        expect(readFileSync(file, 'utf8')).toBe('[2026-10-22T09:30:00.000Z] ERROR: PHP server exited with code 143\n');
    });

    it('appends to the file', () => {
        const write = createLogFile(file, { now: at });

        write('INFO', ['one']);
        write('INFO', ['two']);

        expect(readFileSync(file, 'utf8').split('\n').filter(Boolean)).toHaveLength(2);
    });

    it('keeps one old file when the log grows past the limit', () => {
        const write = createLogFile(file, { now: at, maxBytes: 100 });

        write('INFO', ['a'.repeat(60)]);
        write('INFO', ['b'.repeat(60)]);
        write('INFO', ['c'.repeat(60)]);

        const old = join(dir, 'logs', 'electron.old.log');
        expect(readFileSync(old, 'utf8')).toContain('b'.repeat(60));
        expect(readFileSync(old, 'utf8')).not.toContain('a'.repeat(60));
        expect(readFileSync(file, 'utf8')).toContain('c'.repeat(60));
    });

    it('never throws when the file cannot be written', () => {
        mkdirSync(file, { recursive: true });
        const write = createLogFile(file, { now: at });

        expect(() => write('ERROR', ['lost'])).not.toThrow();
    });

    it('skips empty lines', () => {
        const write = createLogFile(file, { now: at });

        write('INFO', []);

        expect(existsSync(file)).toBe(false);
    });
});

describe('copying the console to the log', () => {
    it('keeps the console output and copies each level', () => {
        const original = { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() };
        const target = { ...original };
        const write = vi.fn();

        copyConsoleTo(write, target as unknown as Console);
        target.log('started on port', 8100);
        target.warn('busy');
        target.error('failed');

        expect(original.log).toHaveBeenCalledWith('started on port', 8100);
        expect(write).toHaveBeenCalledWith('INFO', ['started on port', 8100]);
        expect(write).toHaveBeenCalledWith('WARNING', ['busy']);
        expect(write).toHaveBeenCalledWith('ERROR', ['failed']);
    });
});

describe('electron log in an existing file', () => {
    it('rotates a log that is already over the limit', () => {
        mkdirSync(join(dir, 'logs'));
        writeFileSync(file, 'x'.repeat(200));
        const write = createLogFile(file, { now: at, maxBytes: 100 });

        write('INFO', ['fresh']);

        expect(readFileSync(file, 'utf8')).toBe('[2026-10-22T09:30:00.000Z] INFO: fresh\n');
    });
});
