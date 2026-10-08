import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import MenuBar from './MenuBar.vue';
import type { MenuBarApi } from '../api';
import type { MenuBarState } from '../types/menuBar';

function state(overrides: Partial<MenuBarState> = {}): MenuBarState {
    return {
        date: '2026-10-08',
        genres: [
            { id: 'rock', label: 'Rock' },
            { id: 'jazz', label: 'Jazz' },
        ],
        moods: [
            { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀', description: '' },
            { id: 'debugging', label: 'Debugging', emoji: '🐛', description: '' },
        ],
        selection: { genre: 'rock', mood: 'friday-deploy' },
        pick: {
            id: 'rock-friday-deploy-01',
            title: 'Riffs for risky releases',
            curator: null,
            genre: { id: 'rock', label: 'Rock' },
            mood: { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
        },
        presses: 0,
        note: null,
        empty: null,
        actions: { play: true, next: true, surprise: true },
        catalogDate: '2026-10-07',
        hint: null,
        ...overrides,
    };
}

function fakeApi(next: MenuBarState = state()): MenuBarApi {
    return {
        state: vi.fn(async () => next),
        select: vi.fn(async () => next),
        next: vi.fn(async () => next),
        surprise: vi.fn(async () => next),
        play: vi.fn(async () => ({ outcome: 'played' as const, message: null })),
        addPlaylist: vi.fn(async () => undefined),
        openSettings: vi.fn(async () => undefined),
        quit: vi.fn(async () => undefined),
    };
}

function button(wrapper: ReturnType<typeof mount>, text: string) {
    const found = wrapper.findAll('button').find((candidate) => candidate.text() === text);

    if (found === undefined) {
        throw new Error(`No button "${text}"`);
    }

    return found;
}

describe('MenuBar', () => {
    it('shows the command line, the chips, the pick and the catalog date', () => {
        const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

        expect(wrapper.text()).toContain('$ sudo play --genre rock --mood friday-deploy');
        expect(wrapper.text()).toContain('🐛 Debugging');
        expect(wrapper.text()).toContain('Riffs for risky releases');
        expect(wrapper.text()).toContain('Catalog updated 2026-10-07');
    });

    it('says the catalog is the bundled one when it was never synced', () => {
        const wrapper = mount(MenuBar, { props: { state: state({ catalogDate: null }), api: fakeApi() } });

        expect(wrapper.text()).toContain('Bundled catalog');
    });

    it('selects a genre with the current mood, and draws the answer', async () => {
        const api = fakeApi(state({ selection: { genre: 'jazz', mood: 'friday-deploy' } }));
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Jazz').trigger('click');
        await flushPromises();

        expect(api.select).toHaveBeenCalledWith('jazz', 'friday-deploy');
        expect(wrapper.text()).toContain('--genre jazz');
    });

    it('selects a mood with the current genre', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, '🐛 Debugging').trigger('click');

        expect(api.select).toHaveBeenCalledWith('rock', 'debugging');
    });

    it('sends the Next count and its day', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state({ presses: 2 }), api } });

        await button(wrapper, 'Next').trigger('click');

        expect(api.next).toHaveBeenCalledWith(2, '2026-10-08');
    });

    it('asks for a surprise', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Surprise me').trigger('click');

        expect(api.surprise).toHaveBeenCalledOnce();
    });

    it('plays the playlist on the card and shows what the player said', async () => {
        const api = fakeApi();
        api.play = vi.fn(async () => ({
            outcome: 'fell-back' as const,
            message: 'Could not start Spotify. Opened the playlist in your browser instead.',
        }));
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Play').trigger('click');
        await flushPromises();

        expect(api.play).toHaveBeenCalledWith('rock-friday-deploy-01');
        expect(wrapper.text()).toContain('Could not start Spotify.');
    });

    it('shows the permission hint when there is one', () => {
        const hint = 'The first time you press Play, macOS asks if Sudo Play may control Spotify.';
        const wrapper = mount(MenuBar, { props: { state: state({ hint }), api: fakeApi() } });

        expect(wrapper.text()).toContain(hint);
    });

    it('disables Play, Next and Surprise me in the empty state', () => {
        const empty = state({
            pick: null,
            empty: 'No playlists in the catalog yet',
            actions: { play: false, next: false, surprise: false },
        });
        const wrapper = mount(MenuBar, { props: { state: empty, api: fakeApi() } });

        expect(wrapper.text()).toContain('No playlists in the catalog yet');
        for (const label of ['Play', 'Next', 'Surprise me']) {
            expect(button(wrapper, label).attributes('disabled')).toBeDefined();
        }
    });

    it('opens the contributing page from the footer', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Add a playlist').trigger('click');

        expect(api.addPlaylist).toHaveBeenCalledOnce();
    });

    it('opens the settings window from the footer', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Settings').trigger('click');

        expect(api.openSettings).toHaveBeenCalledOnce();
    });

    it('says so when the settings window cannot be opened', async () => {
        const api = fakeApi();
        api.openSettings = vi.fn(async () => {
            throw new Error('The app answered 500');
        });
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Settings').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toBe('Could not open the settings. Please try again.');
    });

    it('quits from the footer', async () => {
        const api = fakeApi();
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Quit').trigger('click');

        expect(api.quit).toHaveBeenCalledOnce();
    });

    // The window has a fixed size. When the content is taller, only the top part
    // scrolls, so the buttons and the footer never go below the window edge.
    it('keeps the buttons and the footer outside the part that scrolls', () => {
        const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });
        const scroller = wrapper.find('.overflow-y-auto');

        expect(scroller.exists()).toBe(true);

        const labels = scroller.findAll('button').map((candidate) => candidate.text());

        expect(scroller.text()).toContain('$ sudo play');
        expect(scroller.text()).toContain('Riffs for risky releases');
        for (const label of ['Play', 'Next', 'Surprise me', 'Add a playlist', 'Settings', 'Quit']) {
            expect(labels).not.toContain(label);
        }
        expect(scroller.find('footer').exists()).toBe(false);
    });

    it('asks for the state again when the window gets focus, keeping the count', async () => {
        const api = fakeApi(state({ catalogDate: '2026-10-08' }));
        const wrapper = mount(MenuBar, { props: { state: state({ presses: 1 }), api }, attachTo: document.body });

        window.dispatchEvent(new Event('focus'));
        await flushPromises();

        expect(api.state).toHaveBeenCalledWith(1, '2026-10-08');
        expect(wrapper.text()).toContain('Catalog updated 2026-10-08');
        wrapper.unmount();
    });
});
