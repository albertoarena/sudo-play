import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import ChipGroup from './ChipGroup.vue';

const moods = [
    { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
    { id: 'debugging', label: 'Debugging', emoji: '🐛' },
];

describe('ChipGroup', () => {
    it('renders one chip per option, with its emoji', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: null } });

        const chips = wrapper.findAll('button');

        expect(chips).toHaveLength(2);
        expect(chips[0]?.text()).toBe('🚀 Friday Deploy');
    });

    it('emits the id of the chip that was clicked', async () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'friday-deploy' } });

        await wrapper.findAll('button')[1]?.trigger('click');

        expect(wrapper.emitted('select')).toEqual([['debugging']]);
    });

    it('marks the selected chip, and only that one', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'debugging' } });

        const pressed = wrapper.findAll('button').map((chip) => chip.attributes('aria-pressed'));

        expect(pressed).toEqual(['false', 'true']);
    });

    it('does not emit again for the chip already selected', async () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'debugging' } });

        await wrapper.findAll('button')[1]?.trigger('click');

        expect(wrapper.emitted('select')).toBeUndefined();
    });

    it('names the group for screen readers', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: [], selected: null } });

        expect(wrapper.attributes('aria-label')).toBe('Genre');
    });
});
