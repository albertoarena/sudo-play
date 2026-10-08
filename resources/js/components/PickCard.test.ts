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
        const wrapper = mount(PickCard, { props: { pick, note: null, empty: null } });

        expect(wrapper.text()).toContain('Riffs for risky releases');
        expect(wrapper.text()).toContain('Rock');
        expect(wrapper.text()).toContain('🚀 Friday Deploy');
        expect(wrapper.text()).toContain('@octocat');
    });

    it('leaves the curator out when there is none', () => {
        const wrapper = mount(PickCard, { props: { pick: { ...pick, curator: null }, note: null, empty: null } });

        expect(wrapper.text()).not.toContain('@');
    });

    it('shows the fallback note under the pick', () => {
        const note = 'No lo-fi playlist for Debugging yet, here is a jazz one. Add one on GitHub!';
        const wrapper = mount(PickCard, { props: { pick, note, empty: null } });

        expect(wrapper.text()).toContain(note);
    });

    it('renders the empty state with a link to add a playlist', async () => {
        const wrapper = mount(PickCard, {
            props: { pick: null, note: null, empty: 'No playlists in the catalog yet' },
        });

        expect(wrapper.text()).toContain('No playlists in the catalog yet');

        const link = wrapper.find('button');
        expect(link.text()).toBe('Add a playlist');

        await link.trigger('click');
        expect(wrapper.emitted('add-playlist')).toHaveLength(1);
    });
});
