import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CommandLine from './CommandLine.vue';

describe('CommandLine', () => {
    it('renders the current selection as a command', () => {
        const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy' } });

        expect(wrapper.text()).toBe('$ sudo play --genre rock --mood friday-deploy');
    });

    it('follows a new selection', async () => {
        const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy' } });

        await wrapper.setProps({ genre: 'jazz', mood: 'debugging' });

        expect(wrapper.text()).toBe('$ sudo play --genre jazz --mood debugging');
    });

    it('leaves out a flag with no value', () => {
        const wrapper = mount(CommandLine, { props: { genre: null, mood: null } });

        expect(wrapper.text()).toBe('$ sudo play');
    });
});
