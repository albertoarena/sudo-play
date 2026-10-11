import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import ChipGroup from './ChipGroup.vue';

const moods = [
    { id: 'friday-deploy', label: 'Friday Deploy', emoji: '🚀' },
    { id: 'debugging', label: 'Debugging', emoji: '🐛' },
];

const genres = [
    { id: 'rock', label: 'Rock' },
    { id: 'jazz', label: 'Jazz' },
    { id: 'lofi', label: 'Lo-fi' },
];

describe('ChipGroup', () => {
    it('renders one chip per option, with its emoji', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: null } });

        const chips = wrapper.findAll('button');

        expect(chips).toHaveLength(2);
        expect(chips[0]?.text()).toBe('🚀 Friday Deploy');
    });

    it('hides the emoji from screen readers, so a chip reads as its label', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: null } });

        const chip = wrapper.findAll('button')[0];
        const hidden = chip?.findAll('[aria-hidden="true"]') ?? [];

        expect(hidden).toHaveLength(1);
        expect(hidden[0]?.text()).toBe('🚀');
    });

    it('draws no hidden part on a chip without an emoji', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: null } });

        expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(false);
        expect(wrapper.findAll('button')[0]?.text()).toBe('Rock');
    });

    it('emits the id of the chip that was clicked', async () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'friday-deploy' } });

        await wrapper.findAll('button')[1]?.trigger('click');

        expect(wrapper.emitted('select')).toEqual([['debugging']]);
    });

    it('marks the selected chip, and only that one', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'debugging' } });

        const checked = wrapper.findAll('button').map((chip) => chip.attributes('aria-checked'));

        expect(checked).toEqual(['false', 'true']);
    });

    // A screen reader then says "Debugging, radio button, 2 of 2" rather than
    // "toggle button", and knows only one chip in the group can be on.
    it('is a radio group of radios', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'debugging' } });

        expect(wrapper.attributes('role')).toBe('radiogroup');
        expect(wrapper.findAll('button').map((chip) => chip.attributes('role'))).toEqual(['radio', 'radio']);
        expect(wrapper.find('[aria-pressed]').exists()).toBe(false);
    });

    it('is one Tab stop, on the selected chip', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: 'jazz' } });

        expect(wrapper.findAll('button').map((chip) => chip.attributes('tabindex'))).toEqual(['-1', '0', '-1']);
    });

    it('puts the Tab stop on the first chip when none is selected', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: null } });

        expect(wrapper.findAll('button').map((chip) => chip.attributes('tabindex'))).toEqual(['0', '-1', '-1']);
    });

    describe('arrow keys', () => {
        it.each([
            ['ArrowRight', 'lofi'],
            ['ArrowDown', 'lofi'],
            ['ArrowLeft', 'rock'],
            ['ArrowUp', 'rock'],
            ['Home', 'rock'],
            ['End', 'lofi'],
        ])('%s selects %s and moves the focus there', async (key, expected) => {
            const wrapper = mount(ChipGroup, {
                props: { label: 'Genre', options: genres, selected: 'jazz' },
                attachTo: document.body,
            });

            await wrapper.findAll('button')[1]?.trigger('keydown', { key });

            expect(wrapper.emitted('select')).toEqual([[expected]]);
            expect(document.activeElement?.textContent?.trim()).toBe(genres.find((g) => g.id === expected)?.label);
            wrapper.unmount();
        });

        it('wraps round from the last chip to the first, and back', async () => {
            const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: 'lofi' } });

            await wrapper.findAll('button')[2]?.trigger('keydown', { key: 'ArrowRight' });
            await wrapper.setProps({ selected: 'rock' });
            await wrapper.findAll('button')[0]?.trigger('keydown', { key: 'ArrowLeft' });

            expect(wrapper.emitted('select')).toEqual([['rock'], ['lofi']]);
        });

        it('stops the arrow key scrolling the window', async () => {
            const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: 'jazz' } });
            const event = new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true, cancelable: true });

            wrapper.findAll('button')[1]?.element.dispatchEvent(event);

            expect(event.defaultPrevented).toBe(true);
        });

        it('leaves other keys alone', async () => {
            const wrapper = mount(ChipGroup, { props: { label: 'Genre', options: genres, selected: 'jazz' } });
            const event = new KeyboardEvent('keydown', { key: 'n', bubbles: true, cancelable: true });

            wrapper.findAll('button')[1]?.element.dispatchEvent(event);

            expect(event.defaultPrevented).toBe(false);
            expect(wrapper.emitted('select')).toBeUndefined();
        });
    });

    it('fills the selected chip and shows only an edge on the rest', () => {
        const wrapper = mount(ChipGroup, { props: { label: 'Mood', options: moods, selected: 'debugging' } });

        const chips = wrapper.findAll('button');

        expect(chips[1]?.classes()).toEqual(expect.arrayContaining(['bg-accent', 'text-on-accent']));
        expect(chips[0]?.classes()).toContain('border-edge');
        expect(chips[0]?.classes()).not.toContain('bg-accent');
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
