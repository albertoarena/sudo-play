import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import PickCard from './PickCard.vue';
import type { Pick } from '../types/menuBar';

const pick: Pick = {
    id: 'rock-friday-deploy-01',
    title: 'Riffs for risky releases',
    curator: 'octocat',
    genre: { id: 'rock', label: 'Rock' },
    mood: { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
};

describe('PickCard', () => {
    it('renders the title, the genre, the mood and the curator', () => {
        const wrapper = mount(PickCard, { props: { pick, fallback: false, empty: null } });

        expect(wrapper.text()).toContain('Riffs for risky releases');
        expect(wrapper.text()).toContain('Rock');
        expect(wrapper.text()).toContain('🚀 Friday Deploy');
        expect(wrapper.text()).toContain('@octocat');
    });

    it('hides the mood emoji from screen readers', () => {
        const wrapper = mount(PickCard, { props: { pick, fallback: false, empty: null } });

        const hidden = wrapper.findAll('[aria-hidden="true"]');

        expect(hidden).toHaveLength(1);
        expect(hidden[0]?.text()).toBe('🚀');
    });

    it('leaves the curator out when there is none', () => {
        const wrapper = mount(PickCard, { props: { pick: { ...pick, curator: null }, fallback: false, empty: null } });

        expect(wrapper.text()).not.toContain('@');
    });

    it('invites a playlist with a muted link when the pick is a fallback', async () => {
        const wrapper = mount(PickCard, { props: { pick, fallback: true, empty: null } });

        const link = wrapper.find('button');
        expect(link.text()).toBe('Add one');
        expect(link.classes()).toContain('text-muted');

        await link.trigger('click');
        expect(wrapper.emitted('add-playlist')).toHaveLength(1);
    });

    it('keeps the warning colour for real errors, never for a fallback', () => {
        const wrapper = mount(PickCard, { props: { pick, fallback: true, empty: null } });

        expect(wrapper.find('.text-warning').exists()).toBe(false);
    });

    it('shows no link for an exact pick', () => {
        const wrapper = mount(PickCard, { props: { pick, fallback: false, empty: null } });

        expect(wrapper.find('button').exists()).toBe(false);
    });

    it('renders the empty state with a link to add a playlist', async () => {
        const wrapper = mount(PickCard, {
            props: { pick: null, fallback: false, empty: 'No playlists in the catalog yet' },
        });

        expect(wrapper.text()).toContain('No playlists in the catalog yet');

        const link = wrapper.find('button');
        expect(link.text()).toBe('Add a playlist');

        await link.trigger('click');
        expect(wrapper.emitted('add-playlist')).toHaveLength(1);
    });
});
