// The command the menu bar header shows, and how it wraps. Requirements 7.1.

// The prompt sign and the indent of a continued line take two columns each.
const PREFIX = 2;

// A line that continues ends with " \".
const CONTINUE = 2;

/**
 * The command in parts that never split: the command itself, then each flag
 * with its value.
 */
export function commandParts(genre: string | null, mood: string | null): string[] {
    const parts = ['sudo play'];

    if (genre !== null) {
        parts.push(`--genre ${genre}`);
    }

    if (mood !== null) {
        parts.push(`--mood ${mood}`);
    }

    return parts;
}

function width(line: string[]): number {
    return PREFIX + line.join(' ').length;
}

/**
 * Splits the parts into lines of at most `columns` characters, the way a
 * shell user would: a line breaks only between parts, and a line that
 * continues keeps room for its trailing backslash. A part wider than the
 * line gets a line of its own and is never split.
 */
export function wrapCommand(parts: string[], columns: number): string[][] {
    const lines: string[][] = [];
    let line: string[] = [];

    parts.forEach((part, index) => {
        const rest = parts.slice(index);

        if (line.length === 0 || width([...line, ...rest]) <= columns || width([...line, part]) + CONTINUE <= columns) {
            line.push(part);

            return;
        }

        lines.push(line);
        line = [part];
    });

    lines.push(line);

    return lines;
}
