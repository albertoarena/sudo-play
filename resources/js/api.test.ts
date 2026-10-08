import { describe, expect, it, vi } from 'vitest';
import { createMenuBarApi } from './api';

function fakeFetch(body: unknown = {}, status = 200) {
    return vi.fn(
        async (): Promise<Response> =>
            new Response(status === 204 ? null : JSON.stringify(body), {
                status,
                headers: { 'Content-Type': 'application/json' },
            }),
    );
}

function sent(fetcher: ReturnType<typeof fakeFetch>): { url: string; init: RequestInit } {
    const call = fetcher.mock.calls[0] as unknown as [string, RequestInit];

    return { url: call[0], init: call[1] };
}

describe('menu bar api', () => {
    it('posts JSON with the XSRF token from the cookie', async () => {
        const fetcher = fakeFetch({ date: '2026-10-08' });
        const api = createMenuBarApi(fetcher, () => 'theme=dark; XSRF-TOKEN=abc%3D%3D');

        await api.select('jazz', 'debugging');

        const { url, init } = sent(fetcher);
        expect(url).toBe('/menu-bar/select');
        expect(init.method).toBe('POST');
        expect(init.body).toBe(JSON.stringify({ genre: 'jazz', mood: 'debugging' }));
        expect(init.headers).toMatchObject({
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': 'abc==',
        });
    });

    it('sends the Next count and its day', async () => {
        const fetcher = fakeFetch();
        const api = createMenuBarApi(fetcher, () => '');

        await api.next(2, '2026-10-08');

        expect(sent(fetcher).init.body).toBe(JSON.stringify({ presses: 2, date: '2026-10-08' }));
    });

    it('asks for the state with the count in the query', async () => {
        const fetcher = fakeFetch();
        const api = createMenuBarApi(fetcher, () => '');

        await api.state(1, '2026-10-08');

        const { url, init } = sent(fetcher);
        expect(url).toBe('/menu-bar/state?presses=1&date=2026-10-08');
        expect(init.method).toBe('GET');
    });

    it('returns what the server answered', async () => {
        const api = createMenuBarApi(fakeFetch({ outcome: 'played', message: null }), () => '');

        await expect(api.play('jazz-debugging-01')).resolves.toEqual({ outcome: 'played', message: null });
    });

    it('accepts an empty answer', async () => {
        const api = createMenuBarApi(fakeFetch(null, 204), () => '');

        await expect(api.quit()).resolves.toBeUndefined();
    });

    it('throws when the server refuses', async () => {
        const api = createMenuBarApi(fakeFetch({ message: 'no' }, 422), () => '');

        await expect(api.surprise()).rejects.toThrow('422');
    });
});
