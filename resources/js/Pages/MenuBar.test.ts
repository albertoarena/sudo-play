import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import MenuBar from './MenuBar.vue';

describe('MenuBar', () => {
    it('shows the command line', () => {
        const wrapper = mount(MenuBar);

        expect(wrapper.text()).toContain('$ sudo play');
    });
});
