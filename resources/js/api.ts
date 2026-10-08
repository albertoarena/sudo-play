// The menu bar endpoints, typed. Routes are in routes/web.php.
//
// Plain fetch, so there is no extra package. Laravel puts its CSRF token in the
// XSRF-TOKEN cookie, and accepts it back in the X-XSRF-TOKEN header.

import type { MenuBarState, PlayResponse } from './types/menuBar';

type Fetcher = (url: string, init: RequestInit) => Promise<Response>;

export interface MenuBarApi {
    state(presses: number, date: string): Promise<MenuBarState>;
    select(genre: string, mood: string): Promise<MenuBarState>;
    next(presses: number, date: string): Promise<MenuBarState>;
    surprise(): Promise<MenuBarState>;
    play(playlist: string): Promise<PlayResponse>;
    addPlaylist(): Promise<void>;
    quit(): Promise<void>;
}

function xsrfToken(cookies: string): string {
    const entry = cookies
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    return entry === undefined ? '' : decodeURIComponent(entry.slice('XSRF-TOKEN='.length));
}

export function createMenuBarApi(
    fetcher: Fetcher = (url, init) => fetch(url, init),
    cookies: () => string = () => document.cookie,
): MenuBarApi {
    async function send<T>(method: 'GET' | 'POST', url: string, body?: object): Promise<T> {
        const response = await fetcher(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(cookies()),
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error(`The app answered ${response.status} to ${url}`);
        }

        return (response.status === 204 ? undefined : await response.json()) as T;
    }

    return {
        state: (presses, date) =>
            send('GET', `/menu-bar/state?${new URLSearchParams({ presses: String(presses), date })}`),
        select: (genre, mood) => send('POST', '/menu-bar/select', { genre, mood }),
        next: (presses, date) => send('POST', '/menu-bar/next', { presses, date }),
        surprise: () => send('POST', '/menu-bar/surprise'),
        play: (playlist) => send('POST', '/menu-bar/play', { playlist }),
        addPlaylist: () => send('POST', '/menu-bar/add-playlist'),
        quit: () => send('POST', '/menu-bar/quit'),
    };
}
