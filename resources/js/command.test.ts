import { describe, expect, it } from 'vitest';
import { commandParts, wrapCommand } from './command';

const parts = ['sudo play', '--genre rock', '--mood friday-deploy'];

describe('commandParts', () => {
    it('keeps each flag and its value together', () => {
        expect(commandParts('rock', 'friday-deploy')).toEqual(parts);
    });

    it('leaves out a flag with no value', () => {
        expect(commandParts(null, 'debugging')).toEqual(['sudo play', '--mood debugging']);
        expect(commandParts(null, null)).toEqual(['sudo play']);
    });
});

describe('wrapCommand', () => {
    // "$ sudo play --genre rock --mood friday-deploy" is 45 columns.

    it('keeps the command on one line when it fits', () => {
        expect(wrapCommand(parts, 45)).toEqual([parts]);
    });

    it('keeps the command on one line when the width is not known', () => {
        expect(wrapCommand(parts, Infinity)).toEqual([parts]);
    });

    it('moves the flag that does not fit to the next line', () => {
        // "$ sudo play --genre rock \" is 26 columns, "  --mood friday-deploy" is 22.
        expect(wrapCommand(parts, 30)).toEqual([['sudo play', '--genre rock'], ['--mood friday-deploy']]);
    });

    it('leaves room for the trailing backslash on a line that continues', () => {
        // "$ sudo play --genre rock" fits in 25, but not with " \" after it.
        expect(wrapCommand(parts, 25)).toEqual([['sudo play'], ['--genre rock'], ['--mood friday-deploy']]);
    });

    it('never breaks inside a flag, even when the flag alone is too wide', () => {
        expect(wrapCommand(parts, 12)).toEqual([['sudo play'], ['--genre rock'], ['--mood friday-deploy']]);
    });
});
