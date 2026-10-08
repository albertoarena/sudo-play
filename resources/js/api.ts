// The menu bar and settings endpoints, typed. Routes are in routes/web.php.
//
// Plain fetch, so there is no extra package. Laravel puts its CSRF token in the
// XSRF-TOKEN cookie, and accepts it back in the X-XSRF-TOKEN header.

import type { MenuBarState, PlayResponse } from './types/menuBar';
import type { SaveResponse, SettingsValues, UpdateCatalogResponse } from './types/settings';

type Fetcher = (url: string, init: RequestInit) => Promise<Response>;

type Send = <T>(method: 'GET' | 'POST', url: string, body?: object) => Promise<T>;

export interface MenuBarApi {
    state(presses: number, date: string): Promise<MenuBarState>;
    select(genre: string, mood: string): Promise<MenuBarState>;
    next(presses: number, date: string): Promise<MenuBarState>;
    surprise(): Promise<MenuBarState>;
    play(playlist: string): Promise<PlayResponse>;
    addPlaylist(): Promise<void>;
    quit(): Promise<void>;
}

export interface SettingsApi {
    save(values: SettingsValues): Promise<SaveResponse>;
    updateCatalog(): Promise<UpdateCatalogResponse>;
    openRepository(): Promise<void>;
}

/** The server refused the input. One message per field, the first it gave. */
export class ValidationError extends Error {
    constructor(public readonly errors: Record<string, string>) {
        super('The app answered 422: some values were refused');
    }
}

function xsrfToken(cookies: string): string {
    const entry = cookies
        .split(';')
        .map((part) => part.trim())
        .find((part) => part.startsWith('XSRF-TOKEN='));

    return entry === undefined ? '' : decodeURIComponent(entry.slice('XSRF-TOKEN='.length));
}

async function refusal(response: Response): Promise<ValidationError | null> {
    const body = (await response.json().catch(() => null)) as { errors?: Record<string, string[]> } | null;

    if (body?.errors === undefined) {
        return null;
    }

    const first: Record<string, string> = {};

    for (const [field, messages] of Object.entries(body.errors)) {
        first[field] = messages[0] ?? '';
    }

    return new ValidationError(first);
}

function sender(fetcher: Fetcher, cookies: () => string): Send {
    return async <T>(method: 'GET' | 'POST', url: string, body?: object): Promise<T> => {
        const response = await fetcher(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(cookies()),
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });

        if (response.status === 422) {
            throw (await refusal(response)) ?? new Error(`The app answered 422 to ${url}`);
        }

        if (!response.ok) {
            throw new Error(`The app answered ${response.status} to ${url}`);
        }

        return (response.status === 204 ? undefined : await response.json()) as T;
    };
}

export function createMenuBarApi(
    fetcher: Fetcher = (url, init) => fetch(url, init),
    cookies: () => string = () => document.cookie,
): MenuBarApi {
    const send = sender(fetcher, cookies);

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

export function createSettingsApi(
    fetcher: Fetcher = (url, init) => fetch(url, init),
    cookies: () => string = () => document.cookie,
): SettingsApi {
    const send = sender(fetcher, cookies);

    return {
        save: (values) => send('POST', '/settings', values),
        updateCatalog: () => send('POST', '/settings/update-catalog'),
        openRepository: () => send('POST', '/settings/open-repository'),
    };
}
