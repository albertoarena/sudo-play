/// <reference types="node" />
import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

// Read as a file, not as an import. Vitest replaces a CSS import with an empty
// string, so `?raw` gives nothing back here. Vitest runs from the project root.
const appCss = readFileSync('resources/css/app.css', 'utf8');

// The colours live in one place, as semantic tokens in app.css, so they can be
// checked here rather than read off a screenshot. The WCAG 2 formula is copied
// in below on purpose: a contrast check that needs a package is a check that
// gets dropped.
//
// WCAG 2 AA asks for 4.5 on text and 3 on the edge of a control that carries
// meaning. Purely decorative colours, the dividers and the border around the
// terminal panel, are exempt under 1.4.11, so they are only checked for being
// defined and different from what they sit on.

const TEXT = 4.5;
const EDGE = 3;

function body(css: string, marker: string): string {
    const start = css.indexOf(marker);

    if (start === -1) {
        throw new Error(`app.css has no "${marker}" block`);
    }

    const open = css.indexOf('{', start);
    let depth = 0;

    for (let i = open; i < css.length; i++) {
        if (css[i] === '{') {
            depth++;
        }

        if (css[i] === '}') {
            depth--;

            if (depth === 0) {
                return css.slice(open + 1, i);
            }
        }
    }

    throw new Error(`the "${marker}" block in app.css is not closed`);
}

function tokens(declarations: string): Record<string, string> {
    const found: Record<string, string> = {};

    for (const match of declarations.matchAll(/(--color-[a-z0-9-]+)\s*:\s*(#[0-9a-fA-F]{6})\s*;/g)) {
        found[match[1] as string] = (match[2] as string).toLowerCase();
    }

    return found;
}

const light = tokens(body(appCss, '@theme'));
const dark = { ...light, ...tokens(body(appCss, '@media (prefers-color-scheme: dark)')) };

const schemes: Record<string, Record<string, string>> = { light, dark };

function colour(scheme: Record<string, string>, name: string): string {
    const value = scheme[`--color-${name}`];

    if (value === undefined) {
        throw new Error(`app.css does not define --color-${name}`);
    }

    return value;
}

function channel(byte: number): number {
    const part = byte / 255;

    return part <= 0.03928 ? part / 12.92 : ((part + 0.055) / 1.055) ** 2.4;
}

function luminance(hex: string): number {
    const red = channel(parseInt(hex.slice(1, 3), 16));
    const green = channel(parseInt(hex.slice(3, 5), 16));
    const blue = channel(parseInt(hex.slice(5, 7), 16));

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

export function contrast(front: string, back: string): number {
    const one = luminance(front);
    const other = luminance(back);
    const lighter = Math.max(one, other);
    const darker = Math.min(one, other);

    return (lighter + 0.05) / (darker + 0.05);
}

interface Pair {
    role: string;
    front: string;
    back: string;
    least: number;
}

const pairs: Pair[] = [
    { role: 'label on the accent fill', front: 'on-accent', back: 'accent', least: TEXT },
    { role: 'label on the accent fill, hovered', front: 'on-accent', back: 'accent-hover', least: TEXT },
    { role: 'accent used as link text', front: 'accent', back: 'page', least: TEXT },
    { role: 'body text', front: 'ink', back: 'page', least: TEXT },
    { role: 'muted text', front: 'muted', back: 'page', least: TEXT },
    { role: 'warning text', front: 'warning', back: 'page', least: TEXT },
    { role: 'edge of an outline control', front: 'edge', back: 'page', least: EDGE },
    { role: 'edge of a field', front: 'edge', back: 'surface', least: EDGE },
    { role: 'focus ring, which sits on the page in the offset gap', front: 'focus', back: 'page', least: EDGE },
    { role: 'command in the terminal panel', front: 'term-command', back: 'term-bg', least: TEXT },
    { role: 'dim text in the terminal panel', front: 'term-dim', back: 'term-bg', least: TEXT },
];

describe('the colour scheme in app.css', () => {
    for (const scheme of ['light', 'dark']) {
        describe(`${scheme} mode`, () => {
            for (const pair of pairs) {
                it(`has enough contrast for the ${pair.role}`, () => {
                    const tokenSet = schemes[scheme] as Record<string, string>;
                    const ratio = contrast(colour(tokenSet, pair.front), colour(tokenSet, pair.back));

                    expect(ratio).toBeGreaterThanOrEqual(pair.least);
                });
            }

            it('keeps the decorative colours defined and visible', () => {
                const tokenSet = schemes[scheme] as Record<string, string>;

                expect(colour(tokenSet, 'divider')).not.toBe(colour(tokenSet, 'page'));
                expect(colour(tokenSet, 'term-edge')).not.toBe(colour(tokenSet, 'term-bg'));
            });
        });
    }

    it('names no font that the app does not load', () => {
        expect(appCss).not.toContain('Instrument Sans');
    });

    // Without an authored focus style the browser draws its own, which on
    // macOS follows the system accent colour and can put orange on a green
    // chip. One rule in app.css covers every control, so no component can
    // forget it.
    it('authors one focus style, in the project colour', () => {
        const rule = /:focus-visible\s*\{[^}]*outline:\s*2px solid var\(--color-focus\)[^}]*outline-offset:\s*2px/;

        expect(appCss).toMatch(rule);
    });
});

describe('the components', () => {
    const components = [
        'resources/js/pages/MenuBar.vue',
        'resources/js/pages/Settings.vue',
        'resources/js/components/ChipGroup.vue',
        'resources/js/components/CommandLine.vue',
        'resources/js/components/PickCard.vue',
    ];

    // Colours belong to the scheme, not to a component. A raw palette class
    // cannot be checked for contrast and cannot follow the dark scheme, so
    // none is allowed in these files.
    for (const path of components) {
        it(`uses the colour tokens, not the palette, in ${path}`, () => {
            const markup = readFileSync(path, 'utf8');
            const palette = /\b(?:emerald|amber|zinc|neutral|gray|slate|stone|green|teal|lime)-\d{2,3}\b/g;

            expect(markup.match(palette)).toBeNull();
        });
    }
});

describe('the contrast formula', () => {
    // The ratios the design critique measured on the old colours. They keep the
    // formula above honest: if these move, the formula is wrong, not the scheme.
    it('reproduces the measurements from the critique', () => {
        expect(contrast('#ffffff', '#009966')).toBeCloseTo(3.65, 1);
        expect(contrast('#009966', '#f4f4f5')).toBeCloseTo(3.32, 1);
        expect(contrast('#71717b', '#09090b')).toBeCloseTo(4.12, 1);
        expect(contrast('#d4d4d8', '#ffffff')).toBeCloseTo(1.48, 1);
    });

    it('gives 21 for black on white and 1 for a colour on itself', () => {
        expect(contrast('#000000', '#ffffff')).toBeCloseTo(21, 5);
        expect(contrast('#007a55', '#007a55')).toBeCloseTo(1, 5);
    });
});
