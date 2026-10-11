<script setup lang="ts">
import { computed, ref } from 'vue';

export interface ChipOption {
    id: string;
    label: string;
    emoji?: string;
}

const props = defineProps<{
    label: string;
    options: ChipOption[];
    selected: string | null;
}>();

const emit = defineEmits<{
    select: [id: string];
}>();

function choose(id: string): void {
    if (id !== props.selected) {
        emit('select', id);
    }
}

// The chip that takes the Tab stop: the selected one, or the first.
const stop = computed((): string | undefined => props.selected ?? props.options[0]?.id);

const chips = ref<HTMLButtonElement[]>([]);

// The radio group keys from the ARIA practices: arrows move and select, and
// wrap round at either end. Home and End jump to the first and last chip.
function target(index: number, key: string): number | null {
    const last = props.options.length - 1;

    switch (key) {
        case 'ArrowRight':
        case 'ArrowDown':
            return index === last ? 0 : index + 1;
        case 'ArrowLeft':
        case 'ArrowUp':
            return index === 0 ? last : index - 1;
        case 'Home':
            return 0;
        case 'End':
            return last;
        default:
            return null;
    }
}

function move(index: number, event: KeyboardEvent): void {
    const to = target(index, event.key);
    const option = to === null ? undefined : props.options[to];

    if (to === null || option === undefined) {
        return;
    }

    event.preventDefault();
    chips.value[to]?.focus();
    choose(option.id);
}
</script>

<template>
    <div role="radiogroup" :aria-label="label" class="flex flex-wrap gap-1">
        <button
            v-for="(option, index) in options"
            :key="option.id"
            ref="chips"
            type="button"
            role="radio"
            :aria-checked="option.id === selected ? 'true' : 'false'"
            :tabindex="option.id === stop ? 0 : -1"
            class="rounded-full border px-2.5 py-1 text-xs transition-colors"
            :class="
                option.id === selected
                    ? 'border-accent bg-accent text-on-accent'
                    : 'border-edge text-ink hover:border-ink'
            "
            @click="choose(option.id)"
            @keydown="move(index, $event)"
        >
            <!-- The emoji is decoration. A screen reader reads the label alone. -->
            <span v-if="option.emoji" aria-hidden="true">{{ option.emoji }}</span>{{ option.emoji ? ` ${option.label}` : option.label }}
        </button>
    </div>
</template>
