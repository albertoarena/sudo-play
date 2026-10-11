import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
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

    describe('when the command does not fit', () => {
        afterEach(() => {
            vi.restoreAllMocks();
        });

        // The window is about 30 columns wide: 300px of line, 10px a character.
        function narrow(): void {
            vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(300);
            vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue({
                width: 100,
            } as DOMRect);
        }

        it('continues on the next line after a backslash, as a shell does', async () => {
            narrow();
            const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy', note: null } });
            await flushPromises();

            const lines = wrapper.findAll('[data-line]');

            expect(lines.map((line) => line.text())).toEqual(['$ sudo play --genre rock \\', '--mood friday-deploy']);
            expect(lines[1]?.element.textContent).toBe('  --mood friday-deploy');
        });

        it('hides the backslash from screen readers and dims it like the prompt sign', async () => {
            narrow();
            const wrapper = mount(CommandLine, { props: { genre: 'rock', mood: 'friday-deploy', note: null } });
            await flushPromises();

            const slash = wrapper.findAll('[data-line]')[0]?.find('[aria-hidden="true"]');

            expect(slash?.text()).toBe('\\');
            expect(slash?.classes()).toContain('text-term-dim');
        });

        it('keeps a command that fits on one line with no backslash', async () => {
            narrow();
            const wrapper = mount(CommandLine, { props: { genre: 'jazz', mood: null, note: null } });
            await flushPromises();

            expect(wrapper.findAll('[data-line]')).toHaveLength(1);
            expect(wrapper.text()).toBe('$ sudo play --genre jazz');
        });
    });
});
