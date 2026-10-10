import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CommandLine from './CommandLine.vue';

describe('CommandLine', () => {
    it('renders the current selection as a command', () => {
        const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy', note: null } });

        expect(wrapper.text()).toBe('$ sudo play --genre rock --mood friday-deploy');
    });

    it('follows a new selection', async () => {
        const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy', note: null } });

        await wrapper.setProps({ genre: 'jazz', mood: 'debugging' });

        expect(wrapper.text()).toBe('$ sudo play --genre jazz --mood debugging');
    });

    it('leaves out a flag with no value', () => {
        const wrapper = mount(CommandLine, { props: { genre: null, mood: null, note: null } });

        expect(wrapper.text()).toBe('$ sudo play');
    });

    it('adds a dimmed comment line when the pick comes from a wider search', () => {
        const wrapper = mount(CommandLine, {
            props: { genre: 'electronic', mood: 'flow-state', note: 'no electronic for flow-state, playing jazz' },
        });

        const lines = wrapper.findAll('p');
        expect(lines).toHaveLength(2);
        expect(lines[0].text()).toBe('$ sudo play --genre electronic --mood flow-state');
        expect(lines[1].text()).toBe('# no electronic for flow-state, playing jazz');
        expect(lines[1].classes()).toContain('text-term-dim');
    });

    it('shows no comment line for an exact pick', () => {
        const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy', note: null } });

        expect(wrapper.findAll('p')).toHaveLength(1);
        expect(wrapper.text()).not.toContain('#');
    });
});
