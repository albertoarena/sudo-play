import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
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
        play: vi.fn(async () => ({ outcome: 'played' as const, message: null, mode: 'autoplay' as const })),
        addPlaylist: vi.fn(async () => undefined),
        openSettings: vi.fn(async () => undefined),
        quit: vi.fn(async () => undefined),
        hide: vi.fn(async () => undefined),
    };
}

function button(wrapper: ReturnType<typeof mount>, text: string) {
    const found = wrapper.findAll('button').find((candidate) => candidate.text() === text);

    if (found === undefined) {
        throw new Error(`No button "${text}"`);
    }

    return found;
}

// A key pressed on the window itself, as when nothing has focus. A test that
// passes a target sends it there instead, and it bubbles up to the window.
function press(key: string, target: EventTarget = document.body, init: KeyboardEventInit = {}): KeyboardEvent {
    const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...init });

    target.dispatchEvent(event);

    return event;
}

describe('MenuBar', () => {
    it('shows the command line, the chips, the pick and the catalog date', () => {
        const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

        expect(wrapper.text()).toContain('$ sudo play --genre rock --mood friday-deploy');
        expect(wrapper.text()).toContain('🐛 Debugging');
        expect(wrapper.text()).toContain('Riffs for risky releases');
        expect(wrapper.text()).toContain('Catalog updated 2026-10-07');
    });

    it('says the playlists are the built-in ones when the catalog was never synced', () => {
        const wrapper = mount(MenuBar, { props: { state: state({ catalogDate: null }), api: fakeApi() } });

        expect(wrapper.get('footer').text()).toContain('Built-in playlists');
        expect(wrapper.text()).not.toContain('Bundled catalog');
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
            mode: 'autoplay' as const,
        }));
        const wrapper = mount(MenuBar, { props: { state: state(), api } });

        await button(wrapper, 'Play').trigger('click');
        await flushPromises();

        expect(api.play).toHaveBeenCalledWith('rock-friday-deploy-01');
        expect(wrapper.text()).toContain('Could not start Spotify.');
    });

    describe('after Play', () => {
        beforeEach(() => {
            vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] });
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('says what is playing in the command line style, for three seconds', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();

            const line = wrapper.get('[role="status"]');
            expect(line.text()).toBe('▶ playing "Riffs for risky releases"');
            expect(line.classes()).toEqual(expect.arrayContaining(['font-mono', 'text-muted']));
            expect(line.classes()).not.toContain('text-warning');

            vi.advanceTimersByTime(2900);
            await flushPromises();
            expect(wrapper.text()).toContain('playing "Riffs for risky releases"');

            vi.advanceTimersByTime(100);
            await flushPromises();
            expect(wrapper.text()).not.toContain('playing "Riffs for risky releases"');
        });

        // In Open only mode Spotify opens the playlist and waits for its own
        // Play press, so "playing" would not be true.
        it('says the playlist opened in Spotify in Open only mode', async () => {
            const api = fakeApi();
            api.play = vi.fn(async () => ({ outcome: 'played' as const, message: null, mode: 'open-app' as const }));
            const wrapper = mount(MenuBar, { props: { state: state(), api } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();

            const line = wrapper.get('[role="status"]');
            expect(line.text()).toBe('▶ opened "Riffs for risky releases" in Spotify');
            expect(line.classes()).toEqual(expect.arrayContaining(['font-mono', 'text-muted']));
            expect(wrapper.text()).not.toContain('playing');

            vi.advanceTimersByTime(3000);
            await flushPromises();
            expect(wrapper.text()).not.toContain('opened "Riffs for risky releases"');
        });

        // Spotify's web player does not start a playlist opened from a link.
        it('says the playlist opened in the browser in Web player mode', async () => {
            const api = fakeApi();
            api.play = vi.fn(async () => ({ outcome: 'played' as const, message: null, mode: 'web' as const }));
            const wrapper = mount(MenuBar, { props: { state: state(), api } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();

            const line = wrapper.get('[role="status"]');
            expect(line.text()).toBe('▶ opened "Riffs for risky releases" in your browser');
            expect(line.classes()).toEqual(expect.arrayContaining(['font-mono', 'text-muted']));
            expect(wrapper.text()).not.toContain('playing');

            vi.advanceTimersByTime(3000);
            await flushPromises();
            expect(wrapper.text()).not.toContain('opened "Riffs for risky releases"');
        });

        // Starting Spotify can take up to about 20 seconds. Until the answer
        // comes, Play stays off and nothing claims the playlist plays.
        it('keeps Play disabled and says nothing until the answer comes', async () => {
            let answer: (value: Awaited<ReturnType<MenuBarApi['play']>>) => void = () => undefined;
            const api = fakeApi();
            api.play = vi.fn(() => new Promise((resolve) => (answer = resolve)));
            const wrapper = mount(MenuBar, { props: { state: state(), api } });

            await button(wrapper, 'Play').trigger('click');
            vi.advanceTimersByTime(20000);
            await flushPromises();

            expect(button(wrapper, 'Play').attributes('disabled')).toBeDefined();
            expect(wrapper.text()).not.toContain('▶');

            answer({ outcome: 'played', message: null, mode: 'autoplay' });
            await flushPromises();

            expect(button(wrapper, 'Play').attributes('disabled')).toBeUndefined();
            expect(wrapper.get('[role="status"]').text()).toBe('▶ playing "Riffs for risky releases"');
        });

        describe('when Spotify is still starting', () => {
            function starting() {
                const api = fakeApi();
                api.play = vi.fn(async () => ({ outcome: 'starting' as const, message: null, mode: 'autoplay' as const }));

                return { api, wrapper: mount(MenuBar, { props: { state: state(), api } }) };
            }

            it('asks for another Play in a dimmed comment line', async () => {
                const { wrapper } = starting();

                await button(wrapper, 'Play').trigger('click');
                await flushPromises();

                const line = wrapper.get('[role="status"]');
                expect(line.text()).toBe('# spotify is still starting, press Play again');
                expect(line.classes()).toEqual(expect.arrayContaining(['font-mono', 'text-muted']));
                expect(line.classes()).not.toContain('text-warning');
                expect(wrapper.text()).not.toContain('▶');
            });

            it('keeps the window open and the line on screen', async () => {
                const { api, wrapper } = starting();

                await button(wrapper, 'Play').trigger('click');
                await flushPromises();
                vi.advanceTimersByTime(10000);
                await flushPromises();

                expect(api.hide).not.toHaveBeenCalled();
                expect(wrapper.get('[role="status"]').text()).toBe('# spotify is still starting, press Play again');
                expect(button(wrapper, 'Play').attributes('disabled')).toBeUndefined();
            });

            it('takes the line away when Play is pressed again', async () => {
                const { api, wrapper } = starting();

                await button(wrapper, 'Play').trigger('click');
                await flushPromises();
                api.play = vi.fn(async () => ({ outcome: 'played' as const, message: null, mode: 'autoplay' as const }));
                await button(wrapper, 'Play').trigger('click');
                await flushPromises();

                expect(wrapper.get('[role="status"]').text()).toBe('▶ playing "Riffs for risky releases"');
            });
        });

        it('keeps the line for three seconds from the last Play', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();
            vi.advanceTimersByTime(2000);
            await button(wrapper, 'Play').trigger('click');
            await flushPromises();
            vi.advanceTimersByTime(2000);
            await flushPromises();

            expect(wrapper.text()).toContain('playing "Riffs for risky releases"');
        });

        it.each([
            ['fell-back', 'Could not start Spotify. Opened the playlist in your browser instead.'],
            ['failed', 'Could not open Spotify.'],
        ] as const)('shows what the player said instead when it %s', async (outcome, said) => {
            const api = fakeApi();
            api.play = vi.fn(async () => ({ outcome, message: said, mode: 'autoplay' as const }));
            const wrapper = mount(MenuBar, { props: { state: state(), api } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe(said);
            expect(wrapper.text()).not.toContain('▶ playing');
        });

        it('takes the line away when another action starts', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();
            await button(wrapper, 'Next').trigger('click');
            await flushPromises();

            expect(wrapper.text()).not.toContain('▶ playing');
        });
    });

    // Screen readers may miss a live region that is inserted with its text, so
    // there is one region from the start and only its text changes.
    describe('announcements', () => {
        const jazz = state({
            selection: { genre: 'jazz', mood: 'friday-deploy' },
            pick: {
                id: 'jazz-friday-deploy-01',
                title: 'Jazz for deep work',
                curator: null,
                genre: { id: 'jazz', label: 'Jazz' },
                mood: { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
            },
        });

        it('keeps one live region from the start, quiet and out of the layout', () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            expect(wrapper.findAll('[role="status"]')).toHaveLength(1);
            expect(wrapper.get('[role="status"]').text()).toBe('');
            expect(wrapper.get('[role="status"]').classes()).toContain('sr-only');
        });

        it('changes the text of the same region, never a new one', async () => {
            const api = fakeApi();
            api.openSettings = vi.fn(async () => {
                throw new Error('The app answered 500');
            });
            const wrapper = mount(MenuBar, { props: { state: state(), api } });
            const region = wrapper.get('[role="status"]').element;

            await button(wrapper, 'Settings').trigger('click');
            await flushPromises();
            expect(wrapper.get('[role="status"]').element).toBe(region);

            await button(wrapper, 'Play').trigger('click');
            await flushPromises();
            expect(wrapper.get('[role="status"]').element).toBe(region);
            expect(wrapper.get('[role="status"]').classes()).not.toContain('sr-only');
        });

        it('announces a new pick, to screen readers only', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi(jazz) } });

            await button(wrapper, 'Next').trigger('click');
            await flushPromises();

            const region = wrapper.get('[role="status"]');
            expect(region.text()).toBe("Today's pick: Jazz for deep work");
            expect(region.classes()).toContain('sr-only');
        });

        it('announces a new pick that comes with a focus refresh', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi(jazz) }, attachTo: document.body });

            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe("Today's pick: Jazz for deep work");
            wrapper.unmount();
        });

        it('says nothing when the pick stays the same', async () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi(state({ presses: 1 })) } });

            await button(wrapper, 'Next').trigger('click');
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe('');
        });

        // The card already shows the new pick. A line the user can read wins.
        it('does not replace a message that is on screen', async () => {
            const api = fakeApi(jazz);
            api.openSettings = vi.fn(async () => {
                throw new Error('The app answered 500');
            });
            const wrapper = mount(MenuBar, { props: { state: state(), api }, attachTo: document.body });

            await button(wrapper, 'Settings').trigger('click');
            await flushPromises();
            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe('Could not open the settings. Please try again.');
            expect(wrapper.text()).toContain('Jazz for deep work');
            wrapper.unmount();
        });

        it('shows the permission hint while the region is quiet', async () => {
            const hint = 'The first time you press Play, macOS asks if Sudo Play may control Spotify.';
            const wrapper = mount(MenuBar, { props: { state: state({ hint }), api: fakeApi({ ...jazz, hint }) } });

            await button(wrapper, 'Next').trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain(hint);
        });
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

    it('explains a fallback in the command line and opens the contributing page from the card', async () => {
        const api = fakeApi();
        const fallback = state({
            selection: { genre: 'electronic', mood: 'debugging' },
            note: 'no electronic for debugging, playing jazz',
        });
        const wrapper = mount(MenuBar, { props: { state: fallback, api } });

        expect(wrapper.find('header').text()).toContain('$ sudo play --genre electronic --mood debugging');
        expect(wrapper.find('header').text()).toContain('# no electronic for debugging, playing jazz');

        await button(wrapper, 'Add one').trigger('click');

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

    // The footer links are buttons, not captions: a hit target of at least
    // 24px, an underline on hover and on focus, and Quit set apart from
    // Settings so a slip does not close the app.
    describe('footer links', () => {
        const links = ['Add a playlist', 'Settings', 'Quit'];

        it.each(links)('gives "%s" a target of at least 24px', (label) => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            expect(button(wrapper, label).classes()).toEqual(expect.arrayContaining(['min-h-6', 'px-1.5']));
        });

        it.each(links)('underlines "%s" on hover and on focus', (label) => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            expect(button(wrapper, label).classes()).toEqual(
                expect.arrayContaining(['hover:underline', 'focus-visible:underline']),
            );
        });

        it('puts a divider between Settings and Quit', () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });
            const quit = button(wrapper, 'Quit').element;
            const divider = quit.previousElementSibling;

            expect(divider?.getAttribute('aria-hidden')).toBe('true');
            expect(divider?.classList.contains('bg-divider')).toBe(true);
            expect(divider?.previousElementSibling?.textContent?.trim()).toBe('Settings');
        });
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

    // The window asks for the state each time it gets focus, which is each time
    // the icon is clicked. That must not show: no dimmed buttons, no message.
    describe('focus refresh', () => {
        function attached(menuState: MenuBarState, api: MenuBarApi, options: object = {}) {
            return mount(MenuBar, { props: { state: menuState, api }, attachTo: document.body, ...options });
        }

        it('keeps Play, Next and Surprise me enabled while it waits', async () => {
            const api = fakeApi();
            api.state = vi.fn(() => new Promise<MenuBarState>(() => undefined));
            const wrapper = attached(state(), api);

            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            for (const label of ['Play', 'Next', 'Surprise me']) {
                expect(button(wrapper, label).attributes('disabled')).toBeUndefined();
            }
            wrapper.unmount();
        });

        it('does not redraw the card when the answer is the same', async () => {
            let renders = 0;
            const CountingCard = defineComponent({
                props: ['pick', 'fallback', 'empty'],
                setup() {
                    return () => {
                        renders++;

                        return h('section');
                    };
                },
            });
            const wrapper = attached(state(), fakeApi(state()), { global: { stubs: { PickCard: CountingCard } } });
            const before = renders;

            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            expect(renders).toBe(before);
            wrapper.unmount();
        });

        it('says nothing and keeps the state when it fails', async () => {
            const api = fakeApi();
            api.state = vi.fn(async () => {
                throw new Error('The app answered 500');
            });
            const wrapper = attached(state(), api);

            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            expect(wrapper.text()).not.toContain('Something went wrong');
            expect(wrapper.text()).toContain('Riffs for risky releases');
            wrapper.unmount();
        });

        it('keeps a message that is on screen', async () => {
            const api = fakeApi();
            api.openSettings = vi.fn(async () => {
                throw new Error('The app answered 500');
            });
            const wrapper = attached(state(), api);

            await button(wrapper, 'Settings').trigger('click');
            await flushPromises();
            window.dispatchEvent(new Event('focus'));
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe('Could not open the settings. Please try again.');
            wrapper.unmount();
        });

        // A slow answer to the focus refresh must not undo a click made after it.
        it('drops an answer that arrives after an action started', async () => {
            let answer: (value: MenuBarState) => void = () => undefined;
            const api = fakeApi(state({ selection: { genre: 'jazz', mood: 'friday-deploy' } }));
            api.state = vi.fn(() => new Promise<MenuBarState>((resolve) => (answer = resolve)));
            const wrapper = attached(state(), api);

            window.dispatchEvent(new Event('focus'));
            await button(wrapper, 'Jazz').trigger('click');
            await flushPromises();
            answer(state());
            await flushPromises();

            expect(wrapper.text()).toContain('--genre jazz');
            wrapper.unmount();
        });
    });

    describe('keyboard', () => {
        function attached(menuState: MenuBarState = state(), api: MenuBarApi = fakeApi()) {
            return mount(MenuBar, { props: { state: menuState, api }, attachTo: document.body });
        }

        it.each(['Enter', ' '])('plays with %j on the window', async (key) => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            const event = press(key);
            await flushPromises();

            expect(api.play).toHaveBeenCalledWith('rock-friday-deploy-01');
            expect(event.defaultPrevented).toBe(true);
            wrapper.unmount();
        });

        it('leaves Enter to the control that has focus', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            press('Enter', button(wrapper, 'Next').element);
            press(' ', button(wrapper, '🐛 Debugging').element);
            await flushPromises();

            expect(api.play).not.toHaveBeenCalled();
            wrapper.unmount();
        });

        it.each([
            ['n', 'next'],
            ['N', 'next'],
            ['s', 'surprise'],
            ['S', 'surprise'],
        ] as const)('%s asks for %s', async (key, action) => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            press(key);
            await flushPromises();

            expect(api[action]).toHaveBeenCalledOnce();
            wrapper.unmount();
        });

        it('works while a chip has focus', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            press('n', button(wrapper, 'Jazz').element);
            await flushPromises();

            expect(api.next).toHaveBeenCalledOnce();
            wrapper.unmount();
        });

        it('does nothing while a control that takes text has focus', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);
            const field = document.createElement('input');
            document.body.appendChild(field);

            for (const key of ['n', 's', 'Enter', ' ']) {
                press(key, field);
            }
            await flushPromises();

            expect(api.next).not.toHaveBeenCalled();
            expect(api.surprise).not.toHaveBeenCalled();
            expect(api.play).not.toHaveBeenCalled();
            field.remove();
            wrapper.unmount();
        });

        it('does nothing when the action is disabled', async () => {
            const api = fakeApi();
            const wrapper = attached(state({ pick: null, actions: { play: false, next: false, surprise: false } }), api);

            for (const key of ['n', 's', 'Enter', ' ']) {
                press(key);
            }
            await flushPromises();

            expect(api.next).not.toHaveBeenCalled();
            expect(api.surprise).not.toHaveBeenCalled();
            expect(api.play).not.toHaveBeenCalled();
            wrapper.unmount();
        });

        it('does nothing while an answer is on its way', async () => {
            const api = fakeApi();
            api.next = vi.fn(() => new Promise<MenuBarState>(() => undefined));
            const wrapper = attached(state(), api);

            press('n');
            press('n');
            press('Enter');
            await flushPromises();

            expect(api.next).toHaveBeenCalledOnce();
            expect(api.play).not.toHaveBeenCalled();
            wrapper.unmount();
        });

        // Command N and the like belong to the system and Electron, not to us.
        it('leaves a key with Command, Control or Option alone', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            press('n', document.body, { metaKey: true });
            press('s', document.body, { ctrlKey: true });
            press('Enter', document.body, { altKey: true });
            await flushPromises();

            expect(api.next).not.toHaveBeenCalled();
            expect(api.surprise).not.toHaveBeenCalled();
            expect(api.play).not.toHaveBeenCalled();
            wrapper.unmount();
        });

        it('stops listening once the window is gone', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);
            wrapper.unmount();

            press('n');
            await flushPromises();

            expect(api.next).not.toHaveBeenCalled();
        });

        it('hides the window with Esc, wherever the focus is', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);

            press('Escape');
            press('Escape', button(wrapper, 'Next').element);
            await flushPromises();

            expect(api.hide).toHaveBeenCalledTimes(2);
            wrapper.unmount();
        });

        it('hides the window with Esc even in the empty state', async () => {
            const api = fakeApi();
            const wrapper = attached(state({ pick: null, actions: { play: false, next: false, surprise: false } }), api);

            press('Escape');
            await flushPromises();

            expect(api.hide).toHaveBeenCalledOnce();
            wrapper.unmount();
        });

        it('leaves Esc to a control that takes text', async () => {
            const api = fakeApi();
            const wrapper = attached(state(), api);
            const field = document.createElement('input');
            document.body.appendChild(field);

            press('Escape', field);
            press('Escape', document.body, { metaKey: true });
            await flushPromises();

            expect(api.hide).not.toHaveBeenCalled();
            field.remove();
            wrapper.unmount();
        });

        it('says so when the window cannot be hidden', async () => {
            const api = fakeApi();
            api.hide = vi.fn(async () => {
                throw new Error('The app answered 500');
            });
            const wrapper = attached(state(), api);

            press('Escape');
            await flushPromises();

            expect(wrapper.get('[role="status"]').text()).toBe('Could not hide the window. Please try again.');
            wrapper.unmount();
        });

        it('names the shortcut on each button, quietly', () => {
            const wrapper = mount(MenuBar, { props: { state: state(), api: fakeApi() } });

            expect(button(wrapper, 'Play').attributes('title')).toBe('Play (Enter)');
            expect(button(wrapper, 'Next').attributes('title')).toBe('Next (N)');
            expect(button(wrapper, 'Surprise me').attributes('title')).toBe('Surprise me (S)');
            expect(button(wrapper, 'Play').attributes('aria-keyshortcuts')).toBe('Enter Space');
            expect(button(wrapper, 'Next').attributes('aria-keyshortcuts')).toBe('N');
            expect(button(wrapper, 'Surprise me').attributes('aria-keyshortcuts')).toBe('S');
        });
    });
});
