import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import Settings from './Settings.vue';
import { ValidationError } from '../api';
import type { SettingsApi } from '../api';
import type { SettingsPageProps, SettingsValues } from '../types/settings';

function values(overrides: Partial<SettingsValues> = {}): SettingsValues {
    return {
        defaultGenre: 'jazz',
        defaultMood: 'debugging',
        playerMode: 'autoplay',
        reminderEnabled: true,
        reminderTime: '09:00',
        weekdaysOnly: true,
        launchAtLogin: false,
        catalogUrl: 'https://example.com/catalog.yaml',
        ...overrides,
    };
}

function props(overrides: Partial<SettingsPageProps> = {}): SettingsPageProps {
    return {
        settings: values(),
        options: {
            genres: [
                { id: 'rock', label: 'Rock' },
                { id: 'jazz', label: 'Jazz' },
            ],
            moods: [
                { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
                { id: 'debugging', label: 'Debugging', emoji: '🐛' },
            ],
            playerModes: [
                { id: 'autoplay', label: 'Spotify app, autoplay' },
                { id: 'open-app', label: 'Spotify app, open only' },
                { id: 'web', label: 'Web player' },
            ],
        },
        catalogDate: '2026-10-07',
        about: {
            version: '1.2.3',
            repository: 'https://github.com/albertoarena/sudo-play',
            notice: 'Not affiliated with Spotify.',
            privacy: 'No accounts, no analytics. The app only downloads the catalog.',
            curators: ['alice', 'octocat'],
        },
        ...overrides,
    };
}

function fakeApi(overrides: Partial<SettingsApi> = {}): SettingsApi {
    return {
        save: vi.fn(async (saved: SettingsValues) => ({ settings: saved, message: 'Settings saved.' })),
        updateCatalog: vi.fn(async () => ({ updated: true, message: 'Catalog updated.', catalogDate: '2026-10-08' })),
        openRepository: vi.fn(async () => undefined),
        openPrivacy: vi.fn(async () => undefined),
        openContributors: vi.fn(async () => undefined),
        ...overrides,
    };
}

function mountSettings(page: SettingsPageProps = props(), api: SettingsApi = fakeApi()) {
    return mount(Settings, { props: { ...page, api } });
}

function button(wrapper: ReturnType<typeof mountSettings>, text: string) {
    const found = wrapper.findAll('button').find((candidate) => candidate.text() === text);

    if (found === undefined) {
        throw new Error(`No button "${text}"`);
    }

    return found;
}

describe('Settings', () => {
    it('shows the saved values', () => {
        const wrapper = mountSettings(props({ settings: values({ playerMode: 'web', launchAtLogin: true }) }));

        expect((wrapper.get('#default-genre').element as HTMLSelectElement).value).toBe('jazz');
        expect((wrapper.get('#default-mood').element as HTMLSelectElement).value).toBe('debugging');
        expect((wrapper.get('#player-mode-web').element as HTMLInputElement).checked).toBe(true);
        expect((wrapper.get('#reminder-enabled').element as HTMLInputElement).checked).toBe(true);
        expect((wrapper.get('#reminder-time').element as HTMLInputElement).value).toBe('09:00');
        expect((wrapper.get('#weekdays-only').element as HTMLInputElement).checked).toBe(true);
        expect((wrapper.get('#launch-at-login').element as HTMLInputElement).checked).toBe(true);
        expect((wrapper.get('#catalog-url').element as HTMLInputElement).value).toBe(
            'https://example.com/catalog.yaml',
        );
    });

    it('offers the moods with their emoji, and a choice for not chosen', () => {
        const wrapper = mountSettings(props({ settings: values({ defaultGenre: null }) }));

        expect((wrapper.get('#default-genre').element as HTMLSelectElement).value).toBe('');
        expect(wrapper.get('#default-genre').text()).toContain('First in the catalog');
        expect(wrapper.get('#default-mood').text()).toContain('🐛 Debugging');
    });

    it('saves what was changed and shows the answer', async () => {
        const api = fakeApi();
        const wrapper = mountSettings(props(), api);

        await wrapper.get('#default-genre').setValue('rock');
        await wrapper.get('#default-mood').setValue('');
        await wrapper.get('#player-mode-open-app').setValue(true);
        await wrapper.get('#reminder-time').setValue('17:30');
        await wrapper.get('#weekdays-only').setValue(false);
        await wrapper.get('#launch-at-login').setValue(true);
        await button(wrapper, 'Save').trigger('click');
        await flushPromises();

        expect(api.save).toHaveBeenCalledWith(
            values({
                defaultGenre: 'rock',
                defaultMood: null,
                playerMode: 'open-app',
                reminderTime: '17:30',
                weekdaysOnly: false,
                launchAtLogin: true,
            }),
        );
        expect(wrapper.get('[role="status"]').text()).toBe('Settings saved.');
    });

    it('shows what the server kept, which can differ from what was sent', async () => {
        const api = fakeApi({
            save: vi.fn(async () => ({
                settings: values({ launchAtLogin: false }),
                message: 'Settings saved, but launch at login could not be changed.',
            })),
        });
        const wrapper = mountSettings(props(), api);

        await wrapper.get('#launch-at-login').setValue(true);
        await button(wrapper, 'Save').trigger('click');
        await flushPromises();

        expect((wrapper.get('#launch-at-login').element as HTMLInputElement).checked).toBe(false);
        expect(wrapper.get('[role="status"]').text()).toContain('launch at login could not be changed');
    });

    it('shows a refused value next to its field and keeps what was typed', async () => {
        const api = fakeApi({
            save: vi.fn(async () => {
                throw new ValidationError({ catalogUrl: 'Use a link that starts with https://.' });
            }),
        });
        const wrapper = mountSettings(props(), api);

        await wrapper.get('#catalog-url').setValue('http://example.com');
        await button(wrapper, 'Save').trigger('click');
        await flushPromises();

        expect(wrapper.get('#catalog-url-error').text()).toBe('Use a link that starts with https://.');
        expect(wrapper.get('#catalog-url').attributes('aria-invalid')).toBe('true');
        expect((wrapper.get('#catalog-url').element as HTMLInputElement).value).toBe('http://example.com');
        expect(wrapper.get('[role="status"]').text()).toBe('Please check the values marked below.');
    });

    it('says so when saving fails for another reason', async () => {
        const api = fakeApi({
            save: vi.fn(async () => {
                throw new Error('The app answered 500');
            }),
        });
        const wrapper = mountSettings(props(), api);

        await button(wrapper, 'Save').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toBe('Could not save. Please try again.');
    });

    it('turns off the time and weekdays only while the reminder is off', async () => {
        const wrapper = mountSettings();

        await wrapper.get('#reminder-enabled').setValue(false);

        expect(wrapper.get('#reminder-time').attributes('disabled')).toBeDefined();
        expect(wrapper.get('#weekdays-only').attributes('disabled')).toBeDefined();
    });

    it('updates the catalog now and shows the new date', async () => {
        const api = fakeApi();
        const wrapper = mountSettings(props(), api);

        expect(wrapper.text()).toContain('Catalog updated 2026-10-07');

        await button(wrapper, 'Update catalog now').trigger('click');
        await flushPromises();

        expect(api.updateCatalog).toHaveBeenCalledOnce();
        expect(wrapper.text()).toContain('Catalog updated 2026-10-08');
        expect(wrapper.get('#catalog-status').text()).toBe('Catalog updated.');
    });

    it('says the catalog is the bundled one when it was never synced', () => {
        const wrapper = mountSettings(props({ catalogDate: null }));

        expect(wrapper.text()).toContain('Bundled catalog');
    });

    it('shows the version, the GitHub link and the notice in About', async () => {
        const api = fakeApi();
        const wrapper = mountSettings(props(), api);

        expect(wrapper.text()).toContain('Sudo Play 1.2.3');
        expect(wrapper.text()).toContain('Not affiliated with Spotify.');

        await button(wrapper, 'GitHub').trigger('click');

        expect(api.openRepository).toHaveBeenCalledOnce();
    });

    it('shows the privacy summary in About and opens the privacy page', async () => {
        const api = fakeApi();
        const wrapper = mountSettings(props(), api);

        expect(wrapper.text()).toContain('No accounts, no analytics. The app only downloads the catalog.');

        await button(wrapper, 'Privacy').trigger('click');

        expect(api.openPrivacy).toHaveBeenCalledOnce();
    });

    it('lists the curators of the catalog in Credits and opens the contributors page', async () => {
        const api = fakeApi();
        const wrapper = mountSettings(props(), api);

        expect(wrapper.findAll('#curators li').map((item) => item.text())).toEqual(['@alice', '@octocat']);

        await button(wrapper, 'Contributors on GitHub').trigger('click');

        expect(api.openContributors).toHaveBeenCalledOnce();
    });

    it('shows no curator list when the catalog has no curators', () => {
        const wrapper = mountSettings(props({ about: { ...props().about, curators: [] } }));

        expect(wrapper.find('#curators').exists()).toBe(false);
        expect(button(wrapper, 'Contributors on GitHub').exists()).toBe(true);
    });

    it('says when the browser could not open a link', async () => {
        const wrapper = mountSettings(
            props(),
            fakeApi({ openPrivacy: vi.fn(async () => Promise.reject(new Error('no browser'))) }),
        );

        await button(wrapper, 'Privacy').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="status"]').text()).toBe('Could not open the browser.');
    });
});
