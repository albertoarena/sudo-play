// The menu bar state. Mirrors MenuBarState::toArray() in app/MenuBar/MenuBarState.php,
// so the two change together.

import type { Genre, Mood } from './catalog';

export interface Pick {
    id: string;
    title: string;
    curator: string | null;
    genre: Genre;
    /** The chosen mood when the playlist carries it, else the playlist's own first mood. */
    mood: Omit<Mood, 'description'>;
}

export interface MenuBarState {
    /** The local calendar date the state was built for, YYYY-MM-DD. */
    date: string;
    genres: Genre[];
    moods: Mood[];
    selection: {
        genre: string | null;
        mood: string | null;
    };
    pick: Pick | null;
    /** How many times Next was pressed today. The window keeps it in memory. */
    presses: number;
    /**
     * Why the pick comes from another genre or mood, or null. Ids as typed,
     * such as "no electronic for flow-state, playing jazz". The command line
     * draws it as a comment.
     */
    note: string | null;
    /** The empty state line, or null when there is a pick. */
    empty: string | null;
    actions: {
        play: boolean;
        next: boolean;
        surprise: boolean;
    };
    /** The day of the last successful sync, or null for the bundled catalog. */
    catalogDate: string | null;
    /** One line about the macOS permission prompt, in autoplay mode only. */
    hint: string | null;
}

export type PlayOutcome = 'played' | 'fell-back' | 'failed';

export interface PlayResponse {
    outcome: PlayOutcome;
    message: string | null;
}
